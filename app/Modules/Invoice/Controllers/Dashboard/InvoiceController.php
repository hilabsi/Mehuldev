<?php

namespace App\Modules\Invoice\Controllers\Dashboard;

use App\Modules\Driver\Enums\DriverResponses;
use App\Modules\Driver\Models\DriverRating;
use App\Modules\Partner\Models\Partner;
use App\Modules\Trip\Models\Trip;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Invoice\Models\Invoice;
use App\Support\Traits\ModelManipulations;
use App\Modules\Invoice\Enums\InvoiceResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\Invoice\ApiPresenters\InvoicePresenter;

class InvoiceController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Invoice
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'invoices';

  /**
   * InvoiceController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Invoice();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    $this->validate($request, [
      'from_date' => 'nullable|date_format:Y-m-d-H-i',
      'to_date'   => 'nullable|date_format:Y-m-d-H-i',
      'filter'    => 'in:=,>,<',
      'cost'      => 'array',
      'cost.*'    => 'numeric',
    ]);

    $query = Invoice::orderBy('created_at', 'desc');

    if ($request->get('from_date')) {
      $query->whereDate('created_at', '>=', Carbon::createFromFormat('Y-m-d-H-i', $request->get('from_date')));
    }

    if ($request->get('from_date')) {
      $query->whereDate('created_at', '=<', Carbon::createFromFormat('Y-m-d-H-i', $request->get('to_date')));
    }

    if ($request->get('cost')) {
      $query->where('total', '>=', $request->get('cost')[0])->where('total', '<=', $request->get('cost')[1]);
    }

    return success([
                     'rows' => $query->get() -> map(function ($item) {
                       return (new InvoicePresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch All Invoice Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $invoice = $this -> shouldExists('id', $id);

    return success([
                     'rows' => Trip::wherePartnerInvoiceId($invoice->id)->get()->map(function ($item) {
                       return [
                         'id'         => $item->id,
                         'driver'     => $item->name,
                         'rating'     => optional(DriverRating::whereDriverId($item->id)->whereTripId($item->id)->first())->rating ?? 'Not Rated',
                         'source_address'      => $item->pickup_address,
                         'destination_address' => $item->destination_address,
                         'has_stops'           => !!$item->has_stops,
                         'has_driver'          => !!$item->driver_id,
                         'stops'               => $item->stops->map(function ($item) {
                           return [
                             'address' => $item->address,
                             'location'=> $item->location,
                             'order'   => $item->order,
                           ];
                         }),
                         'car_model'   => ($item->car ? getCarModel($item->car) : __('label.mobile.not_associated')). ' - '.$item->status,
                         'cost'        => formatNumber($item->cost + $item->wait_time_cost),
                         'image'       => $item->route_image,
                       ];
                     })
                   ]);
  }

  public function partners($id, Request $request): JsonResponse
  {
    $this->model = new Partner();
    $partner = $this->shouldExists('id', $id);

    $this->validate($request, [
      'from_date' => 'nullable|date_format:Y-m-d-H-i',
      'to_date'   => 'nullable|date_format:Y-m-d-H-i',
      'filter'    => 'in:=,>,<',
      'cost'      => 'array',
      'cost.*'    => 'numeric',
    ]);

    $query = Invoice::wherePartnerId($partner->id);

    if ($request->get('from_date')) {
      $query->whereDate('created_at', '>=', Carbon::createFromFormat('Y-m-d-H-i', $request->get('from_date')));
    }

    if ($request->get('from_date')) {
      $query->whereDate('created_at', '=<', Carbon::createFromFormat('Y-m-d-H-i', $request->get('to_date')));
    }

    if ($request->get('cost')) {
      $query->where('total', '>=', $request->get('cost')[0])->where('total', '<=', $request->get('cost')[1]);
    }


    return success([
                     'rows' => $query->orderBy('created_at', 'desc')->get()->map(function ($item) {
                       return (new InvoicePresenter()) -> item($item);
                     })
                   ]);
  }
  public function update (string $id, Request $request): JsonResponse
  {
    $invoice = $this -> shouldExists('id', $id);

    $this->validate($request, [
      'status' => 'required|in:new,partial,paid'
    ]);

    DB ::beginTransaction();
    try {

      $invoice -> update($request -> only([
                                            'status',
                                          ]));
      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getCode(),
                      $exception -> getMessage()
                    ]);
    }

    return success();
  }

}
