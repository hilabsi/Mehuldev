<?php

namespace App\Modules\Car\Controllers\Dashboard;

use App\Modules\Driver\Models\DriverRating;
use App\Modules\Trip\Models\Trip;
use App\Modules\Trip\Models\TripRequest;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Modules\Car\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Modules\Car\Models\CarView;
use App\Http\Controllers\Controller;
use App\Modules\Car\Enums\CarResponses;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Car\ApiPresenters\CarPresenter;

class CarController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Car
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'cars';

  /**
   * CarController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Car();
  }

  /**
   * Show all models rows.
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    return success([
                     'rows' => ($request->get('partner_id') ? Car ::wherePartnerId($request->get('partner_id'))->get(): Car ::all())
                       -> map(function ($item) {
                         return (new CarPresenter()) -> item($item);
                       })
                   ]);
  }

  /**
   * Show all models rows.
   *
   * @param Request $request
   *
   * @return JsonResponse
   */
  public function search (Request $request): JsonResponse
  {
    $availableFilters = [
      // TODO
    ];

    if (!$request -> hasAny($availableFilters)) {
      return other(CarResponses::FILTER_NOT_AVAILABLE);
    }

    return success([
                     'rows' => CarView ::where($request -> only($availableFilters)) -> get() -> map(function ($item) {
                       return (new CarPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Save model data.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function store (Request $request): JsonResponse
  {
    $this -> validate($request, $this -> model ::validations() -> create());

    DB ::beginTransaction();
    try {

      $car = $this -> model -> create($request -> only([
                                                         'model_id',
                                                         'lpn',
                                                         'color',
                                                         'partner_id',
                                                         'year',
                                                         'type',
                                                         'brand_id',
                                                       ]));

      $car->update($request->only(['categories']));

      $car->setDocuments($request->allFiles());

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $car -> id
                   ]);
  }

  /**
   * Fetch All Car Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $car = $this -> shouldExists('id', $id);

    return success([
                     'car' => (new CarPresenter()) -> item($car)
                   ]);
  }

  /**
   * Update model data.
   *
   * @param String  $id
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update (string $id, Request $request): JsonResponse
  {
    $car = $this -> shouldExists('id', $id);

    $this -> validate($request, $this -> model ::validations() -> edit($car->id));

    if (!$request -> hasAny([
                              'model_id',
                              'lpn',
                              'color',
                              'partner_id',
                              'is_verified',
                              'year',
                              'type',
                              'status',
                              'brand_id',
                              'categories',
                              'category_back',
                              'category_front'
                            ])) {
      return other(CarResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {

      $car -> update($request -> only([
                                        'is_verified',
                                        'model_id',
                                        'lpn',
                                        'color',
                                        'partner_id',
                                        'year',
                                        'type',
                                        'status',
                                        'brand_id',
                                        'categories',
                                      ]));

      $car->setDocuments($request->allFiles());

      $car->partner->refreshCars();

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

  /**
   * Soft-delete car.
   *
   * @param $id
   * @param  Request  $request
   * @return JsonResponse
   */
  public function destroy($id, Request $request): JsonResponse
  {
    $car = Car::find($id);

    if ($car->isDeleted())
      return other(CarResponses::CAR_ALREADY_DELETED);

    DB::beginTransaction();
    try {

      $car->softDelete();

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed();
    }
  }

  public function restore($id): JsonResponse
  {
    $car = Car::find($id);

    if (!$car->isDeleted())
      return other(CarResponses::CAR_ALREADY_DELETED);

    DB::beginTransaction();
    try {

      $car->undoSoftDelete();

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed();
    }
  }

  public function stats($id, Request $request): JsonResponse
  {
    $this->validate($request, [
      'type'  => 'required|in:yearly,monthly,daily',
      'year'  => 'required|digits:4',
      'month'  => 'required_if:type,monthly|max:12|min:1',
    ]);

    $car = $this->shouldExists('id', $id);
    $year = $request->get('year');
    $month = $request->get('month');
    $categories = [];
    $series = [];
    switch ($request->get('type')) {

      case 'yearly':
        $categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($categories as $index => $month) {
          $date = Carbon::createFromFormat('Y-m-d', $year. '-'.($index+1).'-01');
          $series[] = Trip::whereCarId($car->id)->whereStatus('completed')->whereYear('created_at', $date->format('Y'))->whereMonth('created_at', $date->format('m'))->sum('cost');
        }
        break;
      case 'monthly':
        $categories = ['1st Week', '2nd Week', '3rd Week', '4th Week', '5th Week'];
        $base_date = Carbon::createFromFormat('Y-m-d', $year. '-'.$month.'-01');
        foreach ($categories as $index => $week) {
          $date = clone $base_date;
          if(($index === 4) && $date->daysInMonth <= 28)
            $series[] = 0;
          else {
            $start = (clone $date)->addDays($index*7);
            $end = ($index === 4) ? (clone $date)->endOfMonth() : (clone $date)->addDays((($index+1)*7) - 1);
            $series[] = Trip::whereCarId($car->id)->whereStatus('completed')->whereBetween('created_at', [$start, $end])->sum('cost');
          }
        }
        break;
      case 'daily':
        $base_date = Carbon::createFromFormat('Y-m-d', $year. '-'.$month.'-01');
        for ($i = 1; $i <= (clone $base_date)->daysInMonth; $i++) {
          $date = (clone $base_date)->addDays($i - 1);
          $categories[] = $date->shortDayName;
          $series[] = Trip::whereCarId($car->id)->whereStatus('completed')->whereDate('created_at', $date)->sum('cost');
        }
        break;
    }

    return success([
                     'series'     => $series,
                     'categories' => $categories,
                   ]);
  }

  public function trips($id, Request $request): JsonResponse
  {
    $car = $this->shouldExists('id', $id);

    $this->validate($request, [
      'from_date' => 'nullable|date_format:Y-m-d-H-i',
      'to_date'   => 'nullable|date_format:Y-m-d-H-i',
      'filter'    => 'in:=,>,<',
      'cost'      => 'array',
      'cost.*'    => 'numeric',
    ]);

    $query = Trip::whereCarId($id);

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
                     'rows' => $query->orderBy('created_at', 'desc')->get()->map(function ($item) use ($car) {
                       return [
                         'id'         => $item->id,
                         'user'       => optional($item->user)->full_name,
                         'driver'       => optional($item->driver)->name,
                         'partner'    => $car->partner->company_name,
                         'start_time' => $item->type === 'scheduled' ? Carbon::createFromFormat('Y-m-d-H-i-s', $item->scheduled_on)->format('Y-m-d H:i:s') : $item->created_at->format('Y-m-d H:i:s'),
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

  public function paymentMethodsStats($id)
  {
    $stats = Trip::whereCarId($id)->get();

    return success([
                     'stats' => [
                       'all'   => [
                         'total'   => (clone $stats)->filter(function ($item) {return in_array($item->payment_type, ['cash', 'card', 'google', 'apple']);})->count(),
                         'google'  => (clone $stats)->filter(function ($item) {return $item->payment_type === 'google';})->count(),
                         'cash'    => (clone $stats)->filter(function ($item) {return $item->payment_type === 'cash';})->count(),
                         'apple'   => (clone $stats)->filter(function ($item) {return $item->payment_type === 'apple';})->count(),
                         'card'    => (clone $stats)->filter(function ($item) {return $item->payment_type === 'card';})->count()
                       ],
                       'completed'=> [
                         'total'   => (clone $stats)->filter(function ($item) {return ($item->status === 'completed') &&  in_array($item->payment_type, ['cash', 'card', 'google', 'apple']);})->count(),
                         'google'  => (clone $stats)->filter(function ($item) {return ($item->status === 'completed') && $item->payment_type === 'google';})->count(),
                         'cash'    => (clone $stats)->filter(function ($item) {return ($item->status === 'completed') && $item->payment_type === 'cash';})->count(),
                         'apple'   => (clone $stats)->filter(function ($item) {return ($item->status === 'completed') && $item->payment_type === 'apple';})->count(),
                         'card'    => (clone $stats)->filter(function ($item) {return ($item->status === 'completed') && $item->payment_type === 'card';})->count()
                       ],
                       'cancelled'=> [
                         'total'   => (clone $stats)->filter(function ($item) {return ($item->status === 'cancelled') &&  in_array($item->payment_type, ['cash', 'card', 'google', 'apple']);})->count(),
                         'google'  => (clone $stats)->filter(function ($item) {return ($item->status === 'cancelled') && $item->payment_type === 'google';})->count(),
                         'cash'    => (clone $stats)->filter(function ($item) {return ($item->status === 'cancelled') && $item->payment_type === 'cash';})->count(),
                         'apple'   => (clone $stats)->filter(function ($item) {return ($item->status === 'cancelled') && $item->payment_type === 'apple';})->count(),
                         'card'    => (clone $stats)->filter(function ($item) {return ($item->status === 'cancelled') && $item->payment_type === 'card';})->count()
                       ],
                     ],
                   ]);
  }
}
