<?php

namespace App\Modules\Car\Controllers\Mobile;

use App\Modules\Car\Models\CarSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Modules\Car\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Car\Enums\CarResponses;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Car
   */
  protected Car $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected string $type = 'drivers';

  /**
   * CarController constructor.
   */
  public function __construct()
  {
    $this->model = new Car();
  }

  /**
   * Update device id.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function chooseCar(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->chooseCar());

    $car = Car::find($request->get('car_id'));

    if ($car->isBusy())
      return other(CarResponses::CAR_IS_BUSY);

    $driver = auth()->guard('driver')->user();

    if ($driver->hasCar())
      return other(CarResponses::ALREADY_HAS_CAR);

    DB::beginTransaction();
    try {

      $session = CarSession::create([
                                      'car_id' => $car->id,
                                      'driver_id' => $driver->id,
                                    ]);

      $driver->update(['car_id' => $car->id, 'current_session' => $session->id, 'is_online' => 1]);

      $car->update([
                     'driver_id'      => $driver->id,
                     'current_session'=> $session->id
                   ]);


      $car->partner->updateFirestore([
                                       ['path' => 'cars', 'value' => $car->partner->cars()->where('is_verified', 1)->get()->map(function ($item) {
                                         return [
                                           'brand'  => $item->brand->title,
                                           'color'  => $item->color,
                                           'name'   => $item->type,
                                           'id'     => $item->id,
                                           'lpn'    => $item->lpn,
                                           'model'  => getCarModel($item),
                                           'status' => !!!$item->driver_id ? 'active' : 'busy',
                                           'year'   => $item->year,
                                         ];
                                       })->toArray()]
                                     ]);

      $driver->updateFirestore([
                                 ['path' => 'current_car', 'value' => [
                                   'brand'  => $driver->car->brand->title,
                                   'color'  => $driver->car->color,
                                   'id'     => $driver->car->id,
                                   'name'   => $driver->car->type,
                                   'lpn'    => $driver->car->lpn,
                                   'model'  => getCarModel($driver->car),
                                   'status' => 'busy',
                                   'year'   => $driver->car->year,
                                   'hours' => 0,
                                   'online' => true,
                                   'total_cost' => 0,
                                   'trips' => 0
                                 ]]
                               ]);

      DB::commit();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }

    return success();
  }

  public function deselectCar(Request $request): JsonResponse
  {
    $driver = auth()->guard('driver')->user();

    if (! $driver->hasCar())
      return other(CarResponses::NO_CAR);

    DB::beginTransaction();
    try {

      $driver->currentSession()->update(['finished_at' => Carbon::now()]);

      $car = $driver->currentSession->car;

      $car->update([
                     'driver_id'      => null,
                     'current_session'=> null,
                   ]);

      $driver->update(['car_id' => null, 'current_session' => null]);

      $car->partner->updateFirestore([
                                       ['path' => 'cars', 'value' => $car->partner->cars()->where('is_verified', 1)->get()->map(function ($item) {
                                         return [
                                           'brand'  => $item->brand->title,
                                           'name'   => $item->type,
                                           'color'  => $item->color,
                                           'id'     => $item->id,
                                           'lpn'    => $item->lpn,
                                           'model'  => getCarModel($item),
                                           'status' => !!!$item->driver_id ? 'active' : 'busy',
                                           'year'   => $item->year,
                                         ];
                                       })->toArray()]
                                     ]);

      $driver->updateFirestore([
                                 ['path' => 'current_car', 'value' => null]
                               ]);

      DB::commit();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }

    return success();
  }

  public function changeSessionStatus(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->changeSessionStatus());

    $driver = auth()->guard('driver')->user();

    $session = $driver->currentSession;

    if (!$session || $session->finished_at)
      return other(CarResponses::NO_ACTIVE_SESSION);

    if (!!$session->status === ($request->get('status') === 'online'))
      return other(CarResponses::SAME_STATUS);

    if (!!$session->status) {

      $hours = $session->updated_at->diffInHours(Carbon::now());

      $session->increment('hours', $hours);

      $session->refreshFirestore();
    }

    $driver->currentSession()->update(['status' => $request->get('status') === 'online']);

    $driver->update(['is_online' => $request->get('status') === 'online' ? 1 : 0]);

    $driver->updateFirestore([
                               ['path' => 'current_car.online', 'value' => $request->get('status') === 'online']
                             ]);

    return success();
  }
}
