<?php

namespace App\Modules\Driver\Controllers\Mobile;

use App\Modules\Car\Models\CarSession;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Modules\Trip\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Driver\Models\Driver;
use App\Support\Traits\ModelManipulations;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use App\Modules\Driver\Enums\AuthResponses;
use App\Modules\Driver\Enums\DriverResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\Driver\Models\DriverNotification;
use App\Modules\Trip\ApiPresenters\TripPresenter;

class DriverController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Driver
   */
  protected Driver $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected string $type = 'drivers';

  /**
   * DriverController constructor.
   */
  public function __construct()
  {
    $this->model = new Driver();
  }

  /**
   * Update device id.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function updateDeviceId(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->updateDeviceId());

    $driver = auth()->guard('driver')->user();

    DB::beginTransaction();
    try {

      $driver->update($request->only(['device_id']));

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

  /**
   * Change driver's language.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function changeLanguage(Request $request)
  {
    $this->validate($request, $this->model::validations()->updateLanguage());

    $driver = auth()->guard('driver')->user();

    DB::beginTransaction();
    try {

      $driver->update($request->only(['language_id']));

      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed([
                      $exception->getCode(),
                      $exception->getMessage()
                    ]);
    }
  }

  /**
   * Change driver's password
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function changePassword(Request $request)
  {
    $this->validate($request, $this->model::validations()->changePassword());

    $driver = auth()->guard('driver')->user();

    if (!app('hash')->check($request->get('old_password'), $driver->password)) {
      return other(DriverResponses::PASSWORD_NOT_MATCHED);
    }

    DB::beginTransaction();
    try {

      $driver->update(['password' => $request->get('new_password')]);

      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }

  }

  /**
   * Change driver's password
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update(Request $request)
  {
    $this->validate($request, $this->model::validations()->updateProfile());

    $driver = auth()->guard('driver')->user();

    if ($this->model->where('id', '!=', $driver->id)->where(['email' => $request->get('email')])->first()) {
      return other(AuthResponses::EMAIL_OR_PHONE_USED);
    }

    DB::beginTransaction();
    try {

      $driver->update($request->only([
                                       'first_name',
                                       'last_name',
                                       'email',
                                     ]));

      if($request->file('picture')) {

        $driver->setDocuments(['profile' => $request->file('picture')]);

        $driver->refresh();

        $driver->updateFirestore([
                                   ['path' => 'picture'    , 'value' => $driver->getDocument('profile')],
                                 ]);
      }

      $driver->updateFirestore([
                                 ['path' => 'first_name' , 'value' => $driver->first_name],
                                 ['path' => 'last_name'  , 'value' => $driver->last_name],
                                 ['path' => 'email'      , 'value' => $driver->email],
                                 ['path' => 'picture'    , 'value' => $driver->getDocument('profile')],
                               ]);

      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }

  }

  public function updateLocation(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->updateLocation());

    $driver = auth()->guard('driver')->user();

    DB::beginTransaction();
    try {

      $driver->update([
                        'location' => new Point($request->get('location')['lat'], $request->get('location')['lng'], 4326),
                        'heading' => $request->get('heading'),
                        'last_online' => Carbon::now()->toDateTimeString(),
                      ]);

      $driver->refresh();

      $driver->updateFirestore([
                                 ['path' => 'current_location', 'value' => [
                                   'U'        => $driver->location->getLat(),
                                   'k'        => $driver->location->getLng(),
                                   'heading'  => (float)$driver->heading,
                                 ]]
                               ]);

      if ($driver->current_session) {
        $driver->currentSession->car->update(['location' => $driver->location]);
      }

      if ($driver->current_trip) {

        $driver->currentTrip->updateFirestore([
                                                ['path' => 'driver.location.U', 'value' => $driver->location->getLat()],
                                                ['path' => 'driver.location.k', 'value' => $driver->location->getLng()],
                                                ['path' => 'driver.location.heading', 'value' => (float)$driver->heading],
                                              ]);
      }

      DB::commit();

    } catch (\Exception $exception) {
      DB::rollBack();

      return failed([$exception->getMessage()]);
    }

    return success();
  }

  public function notifications(): JsonResponse
  {
    $driver = auth()->guard('driver')->user();

    return success([
                     'notifications' => DriverNotification::whereDriverId($driver->id)->orderBy('created_at', 'desc')->where('deleted_at', '=', null)->get()->map(function ($item) {
                       return [
                         'id'         => $item->id,
                         'title'      => $item->title,
                         'description'=> $item->description,
                         'read'       => $item->isRead(),
                       ];
                     }),
                   ]);
  }

  public function markAsRead(Request $request): JsonResponse
  {
    $this->validate($request, [
      'notification_id' => 'required|exists:d_driver_notifications,id',
    ]);

    $driver = auth()->guard('driver')->user();

    $notification = DriverNotification::whereDriverId($driver->id)->find($request->get('notification_id'));

    if(! $notification)
      return other(DriverResponses::INVALID_NOTIFICATION);

    if (! $notification->isRead() && !$notification->isDeleted())
      $notification->update([
                              'read_at' => Carbon::now()
                            ]);

    return success();
  }

  public function deleteNotification(Request $request): JsonResponse
  {
    $this->validate($request, [
      'notification_id' => 'required|exists:d_driver_notifications,id',
    ]);

    $driver = auth()->guard('driver')->user();

    $notification = DriverNotification::whereDriverId($driver->id)->find($request->get('notification_id'));

    if(! $notification)
      return other(DriverResponses::INVALID_NOTIFICATION);

    if (! $notification->isDeleted())
      $notification->update([
                              'deleted_at' => Carbon::now()
                            ]);

    return success();
  }

  public function createNotification(Request $request): JsonResponse
  {
    $driver = auth()->guard('driver')->user();

    $notification = DriverNotification::create([
                                                 'driver_id' => $driver->id,
                                                 'title' => 'test notification',
                                                 'description' => 'lorem ipsum',
                                               ]);

    notifyFCM([$driver->device_id], [
      'title'  => $notification->title,
      'body'   => $notification->description,
    ]);

    return success();
  }

  public function calcByWeek(Request $request): JsonResponse
  {
    $this->validate($request, [
      'from'  => 'required|date|before:to|date_format:d-m-Y',
      'to'    => 'required|date|after:from|date_format:d-m-Y',
    ]);

    $driver = auth()->guard('driver')->user();

    $from = Carbon::createFromFormat('d-m-Y', $request->get('from'))->format('Y-m-d');
    $to = Carbon::createFromFormat('d-m-Y', $request->get('to'))->format('Y-m-d');

    $sessions = CarSession::whereDriverId($driver->id)
      ->whereDate('created_at', '>=', $from)
      ->whereDate('created_at', '<', $to);

    $trips = Trip::whereDriverId($driver->id)
      ->whereStatus('completed')
      ->whereDate('created_at', '>=', $from)
      ->whereDate('created_at', '<', $to);

    $startDay = Carbon::createFromFormat('Y-m-d', $from);
    $stats = [];
    foreach ([0,1,2,3,4,5,6] as $day) {
      $stats[] = floor((clone $trips)->whereDate('created_at', '=', $startDay->copy()->addDay($day)->format('Y-m-d'))->sum('cost'));
    }


    return success([
                     'stats'        => $stats,
                     'total_trips'  => (clone $trips)->count(),
                     'hours'        => floor((clone $sessions)->sum('hours')),
                     'income'       => formatNumber((clone $trips)->sum('cost')),
                     'cash'         => formatNumber((clone $trips)->wherePaymentType('cash')->sum('cost')),
                     'google'       => formatNumber((clone $trips)->wherePaymentType('google')->sum('cost')),
                     'apple'        => formatNumber((clone $trips)->wherePaymentType('apple')->sum('cost')),
                     'cards'        => formatNumber((clone $trips)->wherePaymentType('card')->sum('cost')),
                   ]);
  }

  public function calcByDay(Request $request): JsonResponse
  {
    $this->validate($request, [
      'day' => 'required|date|date_format:d-m-Y',
    ]);

    $driver = auth()->guard('driver')->user();

    $day = Carbon::createFromFormat('d-m-Y', $request->get('day'))->format('Y-m-d');

    $sessions = CarSession::whereDriverId($driver->id)
      ->whereDate('created_at', '=', $day);

    $trips = Trip::whereDriverId($driver->id)
      ->whereStatus('completed')
      ->whereDate('created_at', '=', $day);

    return success([
                     'total_trips'=> (clone $trips)->count(),
                     'hours'      => floor((clone $sessions)->sum('hours')),
                     'income'     => formatNumber((clone $trips)->sum('cost')),
                     'cash'       => formatNumber((clone $trips)->wherePaymentType('cash')->sum('cost')),
                     'google'     => formatNumber((clone $trips)->wherePaymentType('google')->sum('cost')),
                     'apple'      => formatNumber((clone $trips)->wherePaymentType('apple')->sum('cost')),
                     'cards'      => formatNumber((clone $trips)->wherePaymentType('card')->sum('cost')),
                   ]);
  }
  public function tripsByDay(Request $request): JsonResponse
  {
    $this->validate($request, [
      'day'  => 'required|date|date_format:d-m-Y',
    ]);

    $driver = auth()->guard('driver')->user();

    $day = Carbon::createFromFormat('d-m-Y', $request->get('day'))->format('Y-m-d');

    $trips = Trip::whereDriverId($driver->id)
      ->whereStatus('completed')
      ->whereDate('created_at', '=', $day)
      ->orderBy('created_at', 'desc');

    return success([
                     'total_pages'  => ceil((clone $trips)->count() / 5),
                     'trips'        => (clone $trips)->paginate(5)->map(function ($trip, $s) {
                       return (new TripPresenter())->item($trip);
                     })
                   ]);
  }

  public function tripsByWeek(Request $request): JsonResponse
  {
    $this->validate($request, [
      'from'  => 'required|date|before:to|date_format:d-m-Y',
      'to'    => 'required|date|after:from|date_format:d-m-Y',
    ]);

    $driver = auth()->guard('driver')->user();

    $from = Carbon::createFromFormat('d-m-Y', $request->get('from'))->format('Y-m-d');
    $to = Carbon::createFromFormat('d-m-Y', $request->get('to'))->format('Y-m-d');

    $trips = Trip::whereDriverId($driver->id)
      ->whereStatus('completed')
      ->whereDate('created_at', '>=', $from)
      ->whereDate('created_at', '<', $to)
      ->orderBy('created_at', 'desc');

    return success([
                     'total_pages'  => ceil((clone $trips)->count() / 5),
                     'trips'        => (clone $trips)
                       ->paginate(5)->map(function ($trip, $s) {
                         return (new TripPresenter())->item($trip);
                       })
                   ]);
  }

  public function skipRating(Request $request)
  {
    $driver = auth()->guard('driver')->user();

    $driver->updateFirestore([
                               ['path' => 'rate_trip', 'value' => null]
                             ]);

    return success();
  }

}
