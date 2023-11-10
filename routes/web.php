<?php

/** @var Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Modules\Invoice\Models\Invoice;
use App\Modules\Partner\Models\Partner;
use App\Modules\Settings\Models\Settings;
use App\Modules\Trip\Models\Trip;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Lumen\Routing\Router;
use PHPJasper\PHPJasper;


$router->get('/userlist', function () {
  $result = Trip::all();
  echo '<pre>';
  print_r($result);
  exit;
});

$router->get('/invoice', function () use ($router) {

  $partner = Partner::first();

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
    $model = Invoice::create(
      [
        'partner_id' => $partner->id,
        'invoice_date' => Carbon::now()->format('Y_m_d'),
        'due_date' => Carbon::now()->addWeek()->format('Y_m_d'),
        'code' => $number = settings('partner_invoice_number') ?? Invoice::wherePartnerId($partner->id)->count() + 1,
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

    File::put(storage_path('weekly_invoice.jrxml'), settings('weekly_invoice_jrxml'));

    $input = storage_path('weekly_invoice.jrxml');
//    $input = __DIR__ . '/vendor/geekcom/phpjasper/examples/hello_world.jrxml';

    $jasper = new \JasperPHP\JasperPHP();

    $ou = $jasper->compile($input)->execute();

    $output = storage_path($filename = 'invoices/'.$partner->id.'/'.Carbon::now()->format('Y_m_d_h_i_s'));

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
        "invoice_label" => __("labels.invoice"),
        "invoice_status_label" => __("labels.status"),
        "invoice_date_label" => __("labels.issue_date"),
        "invoice_due_date_label" => __("labels.due_date"),
        "invoice_terms_label" => __("labels.terms"),
        "invoice_notes_label" => __("labels.notes"),
        "heading_item_label" => __("labels.item"),
        "heading_amount_label" => __("labels.quantity"),
        "heading_price_label" => __("labels.price"),
        "heading_tax_label" => __("labels.tax"),
        "heading_subtotal_label" => __("labels.line_total"),
        "invoice_subtotal_label" => __("labels.subtotal"),
        "invoice_total_label" => __("labels.total"),
        "invoice_paid_label" => __("labels.paid"),
        "invoice_amount_due_label" => __("labels.amount_due"),

        "client_number" => $partner->id,
        "client_company_name" => $partner->company_name,
        "client_first_name" => $partner->first_name,
        "client_last_name" => $partner->last_name,
        "client_address" => $partner->address,
        "client_mail" => $partner->email,
        "client_phone" => $partner->phone,


        "invoice_number" => $model->code,
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
      ],
    ];

$oo=    $jasper->process(str_replace('jrxml', 'jasper', $input), $output, ['pdf'], $options['params'])->execute();

    Storage::disk('s3')->put($filename.'.pdf', File::get($output.'.pdf'));

    $model->update(['download_url' => s3($filename.'.pdf')]);

    \Illuminate\Support\Facades\DB::commit();
  } catch (\Exception $e) {
    \Illuminate\Support\Facades\DB::rollBack();
//$o = exec('whomai && /home/ubuntu/lobi/backend/vendor/cossou/jasperphp/src/JasperPHP/../JasperStarter/bin/jasperstarter process /home/ubuntu/lobi/backend/storage/weekly_invoice.jasper -o /home/ubuntu/lobi/backend/storage/invoices/1416d6ba-c99b-46b4-a377-5362f9554cd5/2021_10_06_05_31_12 -f pdf -r /home/ubuntu/lobi/backend/vendor/cossou/jasperphp/src/JasperPHP/../../../../../ -P invoice_label="labels.invoice" invoice_status_label="labels.status" invoice_date_label="labels.issue_date" invoice_due_date_label="labels.due_date" invoice_terms_label="labels.terms" invoice_notes_label="labels.notes" heading_item_label="labels.item" heading_amount_label="labels.quantity" heading_price_label="labels.price" heading_tax_label="labels.tax" heading_subtotal_label="labels.line_total" invoice_subtotal_label="labels.subtotal" invoice_total_label="labels.total" invoice_paid_label="labels.paid" invoice_amount_due_label="labels.amount_due" client_number="1416d6ba-c99b-46b4-a377-5362f9554cd5" client_company_name="Company Test" client_first_name="Partn" client_last_name="Test" client_address="Cairo , egypt" client_mail="partner@test.com" client_phone="01012222000" invoice_number="114" invoice_date="2021_10_06" invoice_due_date="2021_10_13" invoice_total_amount="0,00" invoice_terms="test" invoice_notes="testt" company_name="LOBI" company_adress_line_one="test address" company_adress_line_two="test address 2" company_phone_one="0501111111" company_phone_two="0501111111" company_email="admin@lobi.at" company_website="http://lobi.at" company_uid="VA" company_fn="000000Aw" company_bank_name="test bank" company_iban="VK98 2021 1896 6142 2500" company_bic="ABCDEFJJHH"');

//    print_r($ou);
//    echo $jasper->output();
//echo $o;
print_r($oo);
    throw $e;
  }
});

$router->post('/payments/stripe/webhook', 'PaymentWebhookController@handle');
$router->get('/try', function () {
  dispatch_now(new \App\Modules\Trip\Jobs\RequestNextDriver(\App\Modules\Trip\Models\Trip::find('7ab05369-b198-4340-864f-54119ca2c560')));
});
