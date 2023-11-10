<?php

namespace App\Modules\Trip\Jobs;

use App\Jobs\Job;
use App\Modules\Car\Models\CarSession;
use App\Modules\Driver\Models\Driver;
use App\Modules\Trip\Models\Trip;
use App\Modules\Trip\Models\TripRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\RawMessageFromArray;

class RequestNextDriver extends Job
{
  protected $trip;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($trip)
  {
    $this->trip = Trip::find($trip->id);
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    $this->trip = Trip::find($this->trip->id);

    Log::info('trip request: '.$this->trip->status);

    if ($this->trip->status !== 'pending')
      return;

    // check first if there is an active request and cancel it
    $currentRequest = TripRequest::whereTripId($this->trip->id)->whereStatus('pending')->first();

    if ($currentRequest) {
      $currentRequest->update(['status' => 'ignored']);

      optional($currentRequest->driver)->updateFirestore([
                                                           ['path' => 'request', 'value' => null]
                                                         ]);
    }

    $car = $this->trip->category->cars()
      ->whereIn('current_session', CarSession::whereStatus(1)->pluck('id'))
      ->whereIn('driver_id', Driver::whereStatus('active')->whereIsVerified(1)->whereNotNull('current_session')->whereNull('current_trip')->where('last_online', '>', Carbon::now()->subSeconds(10)->toDateTimeString())->pluck('id'))
      ->whereNotIn('driver_id', array_merge(TripRequest::whereTripId($this->trip->id)->pluck('driver_id')->toArray(), TripRequest::whereStatus('pending')->pluck('driver_id')->toArray()))
      ->distanceSphere('location', $this->trip->pickup_location, settings('driver_search_radius') ?? 20000)
      ->orderByDistanceSphere('location', $this->trip->pickup_location)
      ->first();

    if ($car) {

      $driver = $car->driver;

      $distanceFromUser = $this->distance($this->trip->pickup_location, $driver->location);

      $distanceFromUser = $distanceFromUser > 0 ? $distanceFromUser : 1;

      $tripRequest = TripRequest::create([
                                           'trip_id'     => $this->trip->id,
                                           'category_id' => $this->trip->car_category_id,
                                           'driver_id'   => $driver->id,
                                           'distance'    => $distanceFromUser,
                                         ]
      );

      $this->notifyFCMBackground($driver->device_id, Lang::get('label.mobile.notifications.new_request.title'), Lang::get('label.mobile.notifications.new_request.description'));

      $driver->updateFirestore([
                                 [
                                   'path' => 'request',
                                   'value' => [
                                     'id' => $tripRequest->id,
                                     'trip_ref' => $this->trip->firestore_ref,
                                     'category' => [
                                       'name' => $this->trip->category->name,
                                       'type' => $this->trip->category->type,
                                       'persons' => $this->trip->category->seats,
                                     ],
                                     'distance' => $distanceFromUser,
                                     'timeout' => settings('driver_request_timeout') ?? 60
                                   ]
                                 ]
                               ]);

      if(TripRequest::whereTripId($this->trip->id)->count() < 5) {
        dispatch(new SecondRequestNextDriver($this->trip));
      } else {
        dispatch(new AbortTripAfterLastRequest($this->trip));
      }

    } else {
      $this->trip->update([
                            'status' => 'aborted',
                          ]);

      $this->trip->updateFirestore([
                                     ['path' => 'search_failed', 'value' => true],
                                     ['path' => 'status', 'value' => 'aborted'],
                                   ]);

      $this->trip->user->update(['current_trip' => null]);

      $this->trip->user->updateFirestore([
                                           ['path' => 'current_trip', 'value' => null]
                                         ]);
    }
  }
  private function distance(\Grimzy\LaravelMysqlSpatial\Types\Point $point1, \Grimzy\LaravelMysqlSpatial\Types\Point $point2): int {
    $distance = Driver::selectRaw('st_distance_sphere(ST_GeomFromText(\'POINT('.$point1.')\', 4326, \'axis-order=long-lat\'), ST_GeomFromText(\'POINT('.$point2.')\', 4326, \'axis-order=long-lat\')) as distance')->get();

    if (count($distance->toArray()))
      $distance = $distance[0]->distance ?? 0;
    else
      $distance = 0;

    return $distance;
  }

  private function notifyFCMBackground($device_id, $title, $body) {
    $messaging = app('firebase.messaging');

    $message = new RawMessageFromArray([
                                         'token' => $device_id,
                                         'notification' => [
                                           'title' => $title,
                                           'body' => $body,
                                         ],
                                         'data' => [
                                           'title' => $title,
                                           'body' => $body,
                                         ],
                                         'android' => [
                                           'ttl' => '3600s',
                                           'priority' => 'high',
                                           'notification' => [
                                             'title' => $title,
                                             'body' => $title,
                                             'tag' => 'request',
                                             'vibrate_timings' => ['2.0s', '2.0s', '4.0s'],
                                             'ticker' => $title
                                           ],
                                         ],
                                         'apns' => [
                                           'headers' => [
                                             'apns-priority' => '10',
                                           ],
                                           'payload' => [
                                             'aps' => [
                                               'alert' => [
                                                 'title' => $title,
                                                 'body' => $body,
                                               ],
                                               'badge' => 42,
                                             ],
                                           ],
                                         ]
                                       ]);

    $messaging->send($message);
  }
}
