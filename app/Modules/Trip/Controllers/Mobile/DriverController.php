<?php

namespace App\Modules\Trip\Controllers\Mobile;

use App\Modules\Settings\Models\DriverCancelReason;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Modules\Trip\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Driver\Models\Driver;
use Google\Cloud\Firestore\FieldValue;
use App\Modules\User\Models\UserRating;
use App\Modules\Trip\Models\TripRequest;
use App\Modules\Trip\Enums\TripResponses;
use App\Support\Traits\ModelManipulations;

class DriverController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Trip
   */
  protected Trip $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected string $type = 'trip';

  /**
   * TripController constructor.
   */
  public function __construct()
  {
    $this->model = new Trip();
  }

  public function sendRequest(Request $request):JsonResponse
  {
    $this->validate($request, [
      'driver_id' => 'required|exists:d_drivers,id',
    ]);

    $driver = Driver::find($request->get('driver_id'));

    $trip = Trip::find('07d05d34-ecae-4f55-9258-08092ce0597a');

    $trip->update(['status' => 'pending', 'ended' => 0, 'sent_iam_here' => 0]);

    $tripRequest = TripRequest::create([
      'driver_id' => $request->get('driver_id'),
                                         'trip_id' => $trip->id,
                                         'category_id' => $trip->car_category_id,
                                         'distance' => 5
                                       ]);

    UserRating::whereTripId($trip->id)->whereDriverId($driver->id)->delete();

    $trip->updateFirestore([
                             ['path' => 'ended', 'value' => false],
                             ['path' => 'sent_iam_here', 'value' => false],
                             ['path' => 'status', 'value' => 'pending'],
                           ]);
    $driver->updateFirestore([
                               ['path' => 'current_trip', 'value' => null],
                               ['path' => 'request', 'value' => [
                                 'id' => $tripRequest->id,
                                 'trip_ref' => $trip->firestore_ref,
                                 'category' => [
                                   'name'   => 'LOBI',
                                   'persons'=> 4,
                                 ],
                                 'distance' => 4,
                                 'timeout' => 10
                               ]]
                             ]);

    return success();
  }

  public function rejectRequest(Request $request): JsonResponse
  {
    $this->validate($request, [
      'request_id' => 'required|exists:d_trip_requests,id',
    ]);

    $driver = auth()->guard('driver')->user();

    $driver->updateFirestore([
                               ['path' => 'request', 'value' => null]
                             ]);

    $tripRequest = TripRequest::find($request->get('request_id'));

    $tripRequest->update(['status' => 'rejected']);

    return success();
  }

  public function acceptRequest(Request $request): JsonResponse
  {
    $this->validate($request, [
      'request_id' => 'required|exists:d_trip_requests,id',
    ]);

    $tripRequest = TripRequest::find($request->get('request_id'));

    $driver = auth()->guard('driver')->user();

    $trip = Trip::find($tripRequest->trip_id);

    if (($trip->status !== 'pending') || !$driver->current_session) {

      $driver->updateFirestore([
                                 ['path' => 'request', 'value' => null]
                               ]);

      return other(TripResponses::TRIP_NOT_AVAILABLE);
    }

    DB::beginTransaction();
    try {

      $tripRequest->update(['status' => 'accepted']);

      $trip->update([
                      'status'     => 'pickup',
                      'driver_id'  => $driver->id,
                      'car_id'     => $driver->car_id,
                    ]);

      $driver->update([
                        'current_trip' => $trip->id,
                      ]);

      $time_to_reach = ceil((distance($driver->location, $trip->pickup_location)/ 1000) / 60);

      $trip->updateFirestore([
                               ['path' => 'status',     'value' => 'pickup'],
                               ['path' => 'partner_id',     'value' => $driver->partner_id],
                               ['path' => 'has_driver', 'value' => true],
                               ['path' => 'driver' ,    'value' => [
                                 'first_name'  =>  $driver->first_name,
                                 'last_name'   =>  $driver->last_name,
                                 'image'       =>  $driver->picture ?? settings('default_driver_image') ?? 'https://w7.pngwing.com/pngs/7/618/png-transparent-man-illustration-avatar-icon-fashion-men-avatar-face-fashion-girl-heroes-thumbnail.png',
                                 'rating'      =>  $driver->avgRating() ?? 3,
                                 'location'    => [
                                   'U'        => $driver->location->getLat(),
                                   'k'        => $driver->location->getLng(),
                                   'heading'  => (float)$driver->heading,
                                 ],
                                 'car'         => [
                                   'timeToReach' => $time_to_reach < 1 ? 1 : $time_to_reach,
                                   'type'     => $trip->category->name,
                                   'model'  => getCarModel($driver->car),
                                   'number'   => $driver->car->lpn,
                                   'color'    => $driver->car->color,
                                   'image'    => $driver->car->image ?? settings('default_car_image') ?? 'https://www.iconpacks.net/icons/1/free-car-icon-1057-thumb.png',
                                 ],
                               ]],
                             ]);

      $driver->updateFirestore([
                                 ['path' => 'current_trip', 'value' => $trip->firestore_ref],
                               ]);

      $driver->updateFirestore([
                                 ['path' => 'request', 'value' => null],
                               ]);

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([
                      $exception->getMessage()
                    ]);
    }
  }

  public function sendIamHereMessage(Request $request)
  {
    $driver = auth()->guard('driver')->user();

    $trip = $driver->currentTrip;

    if (! $driver->currentSession || !$trip)
      return other(TripResponses::NO_TRIP_ASSIGNED);

    if($trip->status !== 'pickup')
      return other(TripResponses::TRIP_ALREADY_STARTED);

    if($trip->sent_iam_here)
      return other(TripResponses::ALREADY_SENT);

    DB::beginTransaction();
    try {

      $message = $trip->chat()->create([
                                         'driver_id'  => $driver->id,
                                         'user_id'    => $trip->user_id,
                                         'issuer'     => 'driver',
                                         'message'    => __('label.mobile.notifications.driver_arrived.description'),
                                       ]);

      $trip->update([
                      'sent_iam_here' => true
                    ]);

//      TripLog::create([
//                        'trip_id' => $trip->id,
//                        'type' => 'sent_iam_here',
//                        'driver_location' => $driver->location,
//                        'user_location' => $trip->user->location ?? $driver->location,
//                      ]);

      $trip->updateFirestore([
                               ['path' => 'sent_iam_here', 'value' => true],
                               ['path' => 'chat', 'value' => FieldValue::arrayUnion([[
                                 'issuer'   => $message->issuer,
                                 'message'  => $message->message,
                                 'timestamp'=> $message->created_at->timestamp
                               ]])]
                             ]);

      notifyFCM([$trip->user->device_id], [
        'title'  => __('label.mobile.notifications.driver_arrived.title'),
        'body'   => __('label.mobile.notifications.driver_arrived.description'),
      ]);

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }
  }

  public function startTrip(Request $request): JsonResponse
  {
    $driver = auth()->guard('driver')->user();

    $trip = $driver->currentTrip;

    if (! $driver->currentSession || !$trip)
      return other(TripResponses::NO_TRIP_ASSIGNED);

    if($trip->status !== 'pickup')
      return other(TripResponses::TRIP_ALREADY_STARTED);

    DB::beginTransaction();
    try {

      $trip->update([
                      'status' => 'started',
                      'started_at' => Carbon::now()->timestamp,
                    ]);
//
//      TripLog::create([
//                        'trip_id'         => $trip->id,
//                        'type'            => 'started_at',
//                        'user_location'   => $trip->user->location ?? $driver->location,
//                        'driver_location' => $driver->location,
//                      ]);

      $trip->updateFirestore([
                               ['path' => 'status', 'value' => 'started'],
                               ['path' => 'started_at', 'value' => Carbon::now()->timestamp]
                             ]);

      notifyFCM([$trip->user->device_id], [
        'title'  => __('label.mobile.notifications.you_are_in_your_way.title'),
        'body'   => __('label.mobile.notifications.you_are_in_your_way.description'),
      ]);

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }
  }

  public function reachStop(Request $request): JsonResponse
  {
    $this->validate($request, [
      'order' => 'required|numeric'
    ]);

    $driver = auth()->guard('driver')->user();

    $trip = $driver->currentTrip;

    if (! $driver->currentSession || !$trip)
      return other(TripResponses::NO_TRIP_ASSIGNED);

    if($trip->status !== 'started')
      return other(TripResponses::TRIP_NOT_STARTED);

    $stop = $trip->stops()->whereOrder($request->get('order'))->first();

    if (!$stop)
      return other(TripResponses::INVALID_STOP_ORDER);

    if($stop->reached_at)
      return other(TripResponses::STOP_ALREADY_REACHED);

    DB::beginTransaction();
    try {

      $stop->update([
                      'reached_at' => Carbon::now()
                    ]);

      $trip->updateFirestore([
                               ['path' => 'last_stop', 'value' => $request->get('order')],
                             ]);

//      TripLog::create([
//                        'trip_id'         => $trip->id,
//                        'type'            => 'stop_'.$request->get('order').'_reached',
//                        'user_location'   => $trip->user->location ?? $driver->location,
//                        'driver_location' => $driver->location,
//                      ]);

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }
  }

  public function remainingTime(Request $request): JsonResponse
  {
    $driver = auth()->guard('driver')->user();

    if ($driver->current_trip) {

      $timezone = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, strtoupper($driver->country->alpha2))[0];

      $remaining = Carbon::now($timezone)->addSeconds($request->get('remaining_time'))->format('H:i');

      $driver->currentTrip->updateFirestore([
                                              ['path' => 'estimated_time', 'value' => $remaining],
                                            ]);
    }

    return success();
  }

  public function endTrip(Request $request): JsonResponse
  {
    $driver = auth()->guard('driver')->user();

    $trip = $driver->currentTrip;

    if (! $driver->currentSession || !$trip)
      return other(TripResponses::NO_TRIP_ASSIGNED);

    if($trip->status !== 'started')
      return other(TripResponses::TRIP_NOT_STARTED);

    if($trip->ended)
      return other(TripResponses::ALREADY_ENDED);

    DB::beginTransaction();
    try {
      $session = $driver->currentSession;

      $session->increment('trips');
      $session->increment('total_cost', $trip->cost);

      $session->refreshFirestore();

      $trip->update([
                      'ended' => true,
                    ]);

//      TripLog::create([
//                        'trip_id'         => $trip->id,
//                        'type'            => 'ended_at',
//                        'user_location'   => $trip->user->location ?? $driver->location,
//                        'driver_location' => $driver->location
//                      ]);

      $trip->updateFirestore([
                               ['path' => 'ended', 'value' => true]
                             ]);
      DB::commit();

      $to = null;
      if ($trip->category->type === 'self_calculated')
        $to = 'enter_cost';
      else if ($trip->payment_type === 'cash')
        $to = 'cash';
      else
        $to = 'rating';

      if ($to !== 'rating') {
        $trip->updateFirestore([['path' => 'next_step', 'value' => $to]]);
        return success(['to' => $to]);
      }

      $trip->update(['status' => 'completed']);
      $driver->update(['current_trip' => null]);

//      TripLog::create([
//                        'trip_id'         => $trip->id,
//                        'type'            => 'completed',
//                        'user_location'   => $trip->user->location ?? $driver->location,
//                        'driver_location' => $driver->location
//                      ]);

      $trip->updateFirestore([
                               ['path' => 'next_step', 'value' => 'rating'],
                               ['path' => 'status', 'value' => 'completed']
                             ]);

      $driver->updateFirestore([
                                 ['path' => 'rate_trip', 'value' => $trip->id],
                                 ['path' => 'current_trip', 'value' => null],
                               ]);


      notifyFCM([$trip->user->device_id], [
        'title'  => __('label.mobile.notifications.trip_completed.title'),
        'body'   => __('label.mobile.notifications.trip_completed.description'),
      ]);

      return success(['to' => 'rating',]);

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }
  }

  public function paymentDone(Request $request): JsonResponse
  {
    $driver = auth()->guard('driver')->user();

    $trip = $driver->currentTrip;

    if (! $driver->currentSession || !$trip)
      return other(TripResponses::NO_TRIP_ASSIGNED);

    if(! $trip->ended)
      return other(TripResponses::TRIP_NOT_ENDED);

    if ($trip->category->type === 'self_calculated')
      return other(TripResponses::USE_TAXI_API);

    DB::beginTransaction();
    try {
      $trip->update(['status' => 'completed']);

      $driver->update(['current_trip' => null]);
//
//      TripLog::create([
//                        'trip_id'         => $trip->id,
//                        'type'            => 'completed',
//                        'user_location'   => $trip->user->location ?? $driver->location,
//                        'driver_location' => $driver->location
//                      ]);

      $trip->updateFirestore([
                               ['path' => 'status', 'value' => 'completed']
                             ]);

      $driver->updateFirestore(
        [
          ['path' => 'rate_trip', 'value' => $trip->id],
          ['path' => 'current_trip', 'value' => null],
        ]
      );

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }
  }

  public function requireTaxiPayment(Request $request): JsonResponse
  {
    $this->validate($request, [
      'amount' => 'required|numeric',
    ]);

    $driver = auth()->guard('driver')->user();

    $trip = $driver->currentTrip;

    if (! $driver->currentSession || !$trip)
      return other(TripResponses::NO_TRIP_ASSIGNED);

    if(! $trip->ended)
      return other(TripResponses::TRIP_NOT_ENDED);

    DB::beginTransaction();
    try {

      $trip->update(['cost' => $request->get('amount')]);

      $trip->refresh();

//      TripLog::create([
//                        'trip_id'         => $trip->id,
//                        'type'            => 'add_taxi_cost_'.$trip->cost,
//                        'user_location'   => $trip->user->location,
//                        'driver_location' => $trip->driver->location,
//                      ]);

      if ($trip->payment_type === 'cash') {

        $trip->update(['status' => 'completed']);

        $trip->updateFirestore([
                                 ['path' => 'cost' ,    'value' => formatNumber($trip->cost)],
                                 ['path' => 'status' ,  'value' => 'completed'],
                               ]);
      } else {

        $trip->update(['status' => 'requires_payment']);

        $trip->updateFirestore([
                                 ['path' => 'cost', 'value' => formatNumber($trip->cost)],
                                 ['path' => 'status', 'value' => 'requires_payment'],
                                 ['path' => 'required_payment', 'value' => [
                                   'client_secret'     => null,
                                   'payment_intent_id' => null,
                                   'status'            => 'pending',
                                   'amount'            => $trip->cost*100,
                                   'serverside_waiting' => false,
                                   'type'              => $trip->payment_type,
                                   'image'             => $trip->payment_type === 'card' ? $trip->card->image : null,
                                 ]]
                               ]);
      }

      $driver->update(['current_trip' => null]);

      $driver->updateFirestore([
                                 ['path' => 'current_trip', 'value' => null],
                                 ['path' => 'rate_trip', 'value' => $trip->id],
                               ]);

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }
  }

  public function rateUser(Request $request)
  {
    $this->validate($request, $this->model::validations()->rateTrip());

    $driver = auth()->guard('driver')->user();

    $trip = Trip::whereDriverId($driver->id)->whereId($request->get('trip_id'))->first();

    if (UserRating::whereTripId($trip->id)->whereDriverId($driver->id)->first())
      return other(TripResponses::ALREADY_RATED);

    DB::beginTransaction();
    try {

      UserRating::create([
                           'trip_id' => $trip->id,
                           'driver_id' => $driver->id,
                           'user_id' => $trip->user->id,
                           'rating' => $request->get('rating'),
                           'comment' => $request->get('comment') ?? null
                         ]);

      $trip->user->updateFirestore([
                                     ['path' => 'rating', 'value' => $trip->user->avgRating()],
                                   ]);

      $driver->updateFirestore([
                                 ['path' => 'rate_trip', 'value' => null],
                               ]);
      DB::commit();
    } catch (\Exception $exception) {
      DB::rollBack();

      return failed([$exception->getMessage()]);
    }

    return success();
  }
  public function cancelTrip(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->cancelTrip());

    $driver = auth()->guard('driver')->user();

    $trip = $driver->currentTrip;

    if (! $driver->currentSession || !$trip)
      return other(TripResponses::NO_TRIP_ASSIGNED);

    $trip = Trip::find($request->get('trip_id'));

    DB::beginTransaction();
    try {

      $trip->update(['status' => 'cancelled', 'cancel_reason' => $request->get('cancel_reason')]);

      $trip->user->update([
                            'current_trip' => null,
                          ]);

      $driver->update([
                        'current_trip' => null,
                      ]);

      $trip->updateFirestore(
        [
          ['path' => 'status', 'value' => 'cancelled']
        ]
      );

      $trip->user->updateFirestore([
                                     ['path' => 'current_trip', 'value' => null]
                                   ]);

      $driver->updateFirestore([
                                 ['path' => 'current_trip', 'value' => null]
                               ]);

      if ($trip->payment_intent_id) {

        \Stripe\Stripe::setApiKey(settings('stripe_secret'));

        $re = \Stripe\Refund::create([
                                       'payment_intent' => $trip->payment_intent_id,
                                     ]);
      }

      notifyFCM([$trip->user->device_id], [
        'title'  => __('label.mobile.notifications.trip_cancelled.title'),
        'body'   => __('label.mobile.notifications.trip_cancelled.description'),
      ]);

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }
  }

  public function cancelReasons(Request $request): JsonResponse
  {
    return success([
                     'reasons' => DriverCancelReason::whereLanguageId(getLanguageId(app()->getLocale()))->get()->map(function ($item) {
                       return $item->reason;
                     })
                   ]);
  }

}
