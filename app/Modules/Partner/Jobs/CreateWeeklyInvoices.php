<?php

namespace App\Modules\Partner\Jobs;

use App\Jobs\Job;
use App\Modules\EmailTemplate\Models\EmailTemplate;
use App\Modules\Partner\Mails\GeneralEmail;
use App\Modules\Settings\Models\Settings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPJasper\PHPJasper;
use App\Modules\Trip\Models\Trip;
use Illuminate\Support\Facades\File;
use PHPJasper\Exception\InvalidFormat;
use App\Modules\Partner\Models\Partner;
use App\Modules\Invoice\Models\ClientInvoice;
use Illuminate\Support\Facades\Storage;
use PHPJasper\Exception\InvalidInputFile;
use PHPJasper\Exception\ErrorCommandExecutable;
use PHPJasper\Exception\InvalidCommandExecutable;
use PHPJasper\Exception\InvalidResourceDirectory;

class CreateWeeklyInvoices extends Job
{
  /**
   * Execute the job.
   *
   * @return void
   * @throws ErrorCommandExecutable
   * @throws InvalidCommandExecutable
   * @throws InvalidFormat
   * @throws InvalidInputFile
   * @throws InvalidResourceDirectory
   */
  public function handle()
  {
    foreach (Partner::all() as $partner) {

      $now = Carbon::now();

      $query = Trip::whereHas('driver', function ($query) use ($partner) {
        $query->where('partner_id', $partner->id);
      })->whereDate('created_at', '>=', $now->addWeeks(-1))->whereDate('created_at', '<=', $now);

      $cash   = (clone $query)->wherePaymentType('cash')->sum('cost');
      $apple  = (clone $query)->wherePaymentType('apple')->sum('cost');
      $google = (clone $query)->wherePaymentType('google')->sum('cost');
      $cards  = (clone $query)->wherePaymentType('card')->sum('cost');

      $subtotal = $cards + $apple + $google + $cards;

      $commission = $subtotal * ($partner->percent/100);

      $total = $subtotal - $commission - $cash;
      $total *= -1;

      \Illuminate\Support\Facades\DB::beginTransaction();
      try {
        $model = ClientInvoice::create(
          [
            'partner_id' => $partner->id,
            'invoice_date' => Carbon::now()->format('Y.m.d'),
            'due_date' => Carbon::now()->addWeek()->format('Y.m.d'),
            'code' => $number = settings('partner_invoice_number') ?? ClientInvoice::wherePartnerId($partner->id)->count() + 1,
            'cash' => $cards,
            'apple' => $apple,
            'google' => $google,
            'card' => $cards,
            'commission_percent' => $partner->percent ?? 1,
            'commission_amount' => $commission,
            'total' => $total,
            'terms' => settings('invoice_terms'),
            'notes' => settings('invoice_notes'),
            'status' => 'new',
          ]
        );

        Settings::where('key', 'partner_invoice_number')->update(['value' => $number+1]);

        (clone $query)->update(
          [
            'partner_invoice_id' => $model->id,
          ]
        );

        File::put(resource_path('weekly_invoice.jrxml'), settings('weekly_invoice_jrxml'));

        $input = resource_path('weekly_invoice.jrxml');

        $jasper = new \JasperPHP\JasperPHP();

        $ou = $jasper->compile($input)->execute();

        $output = storage_path($filename = 'invoices/'.$partner->id.'/'.Carbon::now()->format('Y_m_d_H_i_s'));

        if(!File::exists($s = storage_path('invoices'))){
          File::makeDirectory($s);
        }

        if(!File::exists($s = storage_path('invoices/'.$partner->id))){
          File::makeDirectory($s);
        }

        $options = [
          'format' => ['pdf'],
          'locale' => 'en',
          'params' => [
            "invoice_label" => __("invoice"),
            "invoice_status_label" => __("status"),
            "invoice_date_label" => __("issue_date"),
            "invoice_due_date_label" => __("due_date"),

            "client_number" => $partner->id,
            "client_company_name" => $partner->company_name,
            "client_first_name" => $partner->first_name,
            "client_last_name" => $partner->last_name,
            "client_address" => $partner->address,
            "client_mail" => $partner->email,
            "client_phone" => $partner->phone,


            "invoice_number" => \settings('invoice_partner_prefix').str_pad($model->code,6,'0', STR_PAD_LEFT),
            "invoice_date" => $model->invoice_date,
            "invoice_due_date" => $model->due_date,
            "invoice_total_amount" => formatNumber($model->total),
            "invoice_terms" => str_replace('"', '', $model->terms),
            "invoice_notes" => str_replace('"', '', $model->notes),

            "company_name" => settings('company_name'),
            "company_adress_line_one" => settings('address_line_1'),
            "company_adress_line_two" => settings('address_line_2'),
            "company_phone_one" => settings('phone_1'),
            "company_phone_two" => settings('phone_2'),
            "company_email" => settings('email'),
            "company_website" => settings('website'),
            "company_uid" => settings('vat'),
            "company_fn" => settings('register_number'),
            "company_bank_name" => settings('bank_name'),
            "company_iban" => settings('iban'),
            "company_bic" => settings('bic'),

            'header_image'              => s3(settings('invoice_logo') ?? settings('logo')),
          ],
        ];

        $jasper->process(str_replace('jrxml', 'jasper', $input), $output, ['pdf'], $options['params'])->execute();

        Storage::disk('s3')->put($filename.'.pdf', File::get($output.'.pdf'));

        $model->update(['download_url' => s3($filename.'.pdf')]);

        try {

          $email = EmailTemplate::whereTitle('PARTNER_INVOICE_ISSUED_'.strtoupper($partner->language->shortcut))->first() ?? EmailTemplate::whereTitle('PARTNER_INVOICE_ISSUED_DE')->first();

          if ($email)
            Mail::to($partner)->send(new GeneralEmail(str_replace(['##first_name', '##portal_url'], [$partner->first_name, env('PORTAL_URL', 'ttps://portal.lobi.at/')], $email->template), $email->subject));

        } catch (\Exception $exception) {}


        \Illuminate\Support\Facades\DB::commit();
      } catch (\Exception $e) {
        DB::rollBack();
      }
    }
  }
}
