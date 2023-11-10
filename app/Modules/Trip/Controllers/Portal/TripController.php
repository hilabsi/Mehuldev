<?php

namespace App\Modules\Trip\Controllers\Portal;

use App\Modules\Invoice\Models\ClientInvoice;
use App\Modules\Settings\Models\Settings;
use App\Modules\Trip\Models\Trip;
use App\Modules\User\Models\UserRating;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use App\Modules\Trip\ApiPresenters\TripPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class TripController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Trip
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'trips';

  /**
   * TripController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Trip();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (): JsonResponse
  {
    $partnerId = auth()->guard('partner')->user();

    return success([
                     'rows' => Trip ::whereHas('driver', function ($query) use ($partnerId) {
                       $query->where('partner_id', $partnerId);
                     })->get() -> map(function ($item) {
                       return (new TripPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch All Trip Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $trip = $this -> shouldExists('id', $id);

    return success([
                     'trip' => (new TripPresenter()) -> overview($trip)
                   ]);
  }

  public function trips(Request $request): JsonResponse
  {
    $partnerId = auth()->guard('partner')->id();

    $this->validate($request, [
      'from_date' => 'nullable|date_format:Y-m-d-H-i',
      'to_date'   => 'nullable|date_format:Y-m-d-H-i',
      'filter'    => 'in:=,>,<',
      'cost'      => 'array',
      'cost.*'    => 'numeric',
    ]);

    $query = Trip::with('driver')->whereHas('driver', function ($query) use ($partnerId) {
      $query->where('partner_id', $partnerId);
    });

    if ($request->get('from_date')) {
      $query->whereDate('created_at', '>=', Carbon::createFromFormat('Y-m-d-H-i', $request->get('from_date')));
    }

    if ($request->get('from_date')) {
      $query->whereDate('created_at', '=<', Carbon::createFromFormat('Y-m-d-H-i', $request->get('to_date')));
    }

    if ($request->get('cost')) {
      $query->where('cost', '>=', $request->get('cost')[0])->where('cost', '<=', $request->get('cost')[1]);
    }


    return success([
                     'rows' => $query->orderBy('created_at', 'desc')->get()->map(function ($item) {
                       return [
                         'id'         => $item->id,
                         'driver'     => optional($item->driver)->name,
                         'driver_id'  => optional($item->driver)->id,
                         'user'     => optional($item->user)->full_name,
                         'user_id'  => optional($item->user)->id,
                         'rating'     => optional(UserRating::whereTripId($item->id)->first())->rating ?? 'Not Rated',
                         'start_time' => $item->type === 'scheduled' ? Carbon::createFromFormat('Y-m-d-H-i-s', $item->scheduled_on)->format('Y.m.d H:i:s') : $item->created_at->format('Y.m.d H:i:s'),
                         'end_time'   => $item->updated_at->format('Y.m.d H:i:s'),
                         'cost'       => formatNumber($item->cost),
                         'numeric_cost'       => $item->cost,
                         'status'     => $item->status,
                         'payment_method' => $item->payment_type,
                         'created_at' => $item->created_at->format('Y.m.d H:i:s'),
                       ];
                     })
                   ]);
  }
  public function createInvoice($id)
  {
    $trip = $this->shouldExists('id', $id);

    $now = Carbon::now();

    \Illuminate\Support\Facades\DB::beginTransaction();
    try {
      $model = ClientInvoice::create(
        [
          'trip_id' => $trip->id,
          'invoice_date' => Carbon::now()->format('Y.m.d'),
          'due_date' => Carbon::now()->addWeek()->format('Y.m.d'),
          'code'  => $number = settings('client_invoice_number') ?? ClientInvoice::count() + 1,
          'total' => $trip->cost,
          'terms' => settings('invoice_terms'),
          'notes' => settings('invoice_notes'),
        ]
      );

      Settings::where('key', 'client_invoice_number')->update(['value' => $number+1]);

      $trip->update(
        [
          'client_invoice_id' => $model->id,
        ]
      );

      File::put(resource_path('client_invoice.jrxml'), settings('client_invoice_jrxml'));

      $input = resource_path('client_invoice.jrxml');

      $jasper = new \JasperPHP\JasperPHP();

      $jasper->compile($input)->execute();

      $output = storage_path($filename = 'invoices/'.$trip->id.'/'.Carbon::now()->format('Y_m_d_H_i_s'));

      if(!File::exists($s = storage_path('invoices'))){
        File::makeDirectory($s);
      }

      if(!File::exists($s = storage_path('invoices/'.$trip->id))){
        File::makeDirectory($s);
      }
      $netto = ($model->total) / 1.10;
      $options = [
        'format' => ['pdf'],
        'locale' => 'en',
        'params' => [
          "invoice_label" => __("invoice"),
          "invoice_status_label" => __("status"),
          "invoice_date_label" => __("issue_date"),
          "invoice_due_date_label" => __("due_date"),

          'trip_source_address' => $trip->source_address,
          'trip_pickup_address' => $trip->pickup_address,
          'trip_destination_address' => $trip->destination_address,
          'trip_status' => $trip->status,
          'trip_driver' => $trip->driver ? $trip->driver->name : null,
          'trip_car' => $trip->car ? getCarModel($trip->car) : null,
          'trip_cancel_reason' => $trip->cancel_reason,
          'trip_wallet_type' => $trip->wallet_type,
          'trip_payment_type' => $trip->payment_type,
          'trip_cost' => $trip->cost,
          'trip_route_image' => $trip->route_image,
          'trip_sent_iam_here' => $trip->sent_iam_here,
          'trip_distance' => $trip->distance,
          'trip_wait_time_cost' => $trip->wait_time_cost,

          "client_company_name" => $trip->user->business->company_name,
          "client_first_name" => $trip->user->first_name,
          "client_last_name" => $trip->user->last_name,
          "client_address" => $trip->user->business->address,
          "client_mail" => $trip->user->email,
          "client_phone" => $trip->user->getFullPhoneNumber(),


          "invoice_number" => \settings('invoice_client_prefix').str_pad($model->code,4,'0'),
          "invoice_date" => $model->invoice_date,
          "invoice_due_date" => $model->due_date,
          "invoice_total_amount" => formatNumber($model->total),
          "invoice_total_net_amount" => formatNumber($netto),
          "invoice_total_vat_amount" => formatNumber($netto*0.10),
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

      $x = $jasper->process(str_replace('jrxml', 'jasper', $input), $output, ['pdf'], $options['params'])->execute();

      Storage::disk('s3')->put($filename.'.pdf', File::get($output.'.pdf'));

      $model->update(['download_url' => $url = s3($filename.'.pdf')]);

      \Illuminate\Support\Facades\DB::commit();

      return redirect()->to($url);

    } catch (\Exception $e) {

      DB::rollBack();

      return failed([
        $e->getMessage()
      ]);
    }
  }
}
