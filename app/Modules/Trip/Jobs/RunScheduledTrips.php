<?php

namespace App\Modules\Trip\Jobs;

use App\Jobs\Job;
use Carbon\Carbon;
use App\Modules\Trip\Models\Trip;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Kawankoding\Fcm\Fcm;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\RawMessageFromArray;

class RunScheduledTrips extends Job implements ShouldQueue
{
  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {

    foreach (Trip::whereType('scheduled')->whereStatus('pending')->whereNull('did_run')->get() as $trip) {

      app()->setLocale(optional($trip->user->language)->shortcut ?? 'en');

      try {
        if ($trip->scheduled_on) {

          echo 'accessing trip:'.$trip->id.'\n';

          $time = Carbon::createFromFormat('Y-m-d-H-i-s', $trip->scheduled_on);
          echo "trup scheduled on ". $time;
        } else {
          echo 'trip: '.$trip->id.' deleted.\n';
          $trip->delete();
          continue;
        }

        if ($time->between(Carbon::now()->addMinutes(-2), Carbon::now()->addMinutes(2))) {

          echo 'trip: '.$trip->id.' can run.\n';
          $trip->update(['did_run' => Carbon::now()]);

          if($trip->user->current_trip) {
            echo 'trip: '.$trip->id.' cancelled due to user busy';

            $cancel_reason = 'Cancelled due to you was already in a trip.';

            $user = $trip->user;

            // cancel the trip due to the user already has a trip in the same time
            $trip->update(['status' => 'cancelled', 'cancel_reason' => $cancel_reason]);

            $trip->updateFirestore([
                                     ['path' => 'status', 'value' => 'cancelled']
                                   ]);

            $this->notifyFCMBackground($user->device_id,
                                       Lang::get('mobile.notifications.your_scheduled_trip_cancelled.title'),
                                       Lang::get('mobile.notifications.your_scheduled_trip_cancelled.description')
            );
          } else {
            echo 'trip: '.$trip->id.' did run.\n';

            $trip->user->update([
                                  'current_trip' => $trip->id,
                                ]);

            $trip->user->updateFirestore([
                                           ['path' => 'current_trip', 'value'=> $trip->firestore_ref]
                                         ]);

            $trip->user->updateFirestore([
                                           ['path' => 'new_trip', 'value'=> Carbon::now()->toString()]
                                         ]);

            $trip->updateFirestore([
                                     ['path' => 'did_run', 'value' => true],
                                   ]);

            $this->notifyFCMBackground($trip->user->device_id,
                                       'Your scheduled trip is starting',
                                       'We\'re finding a driver for you!'
            );

            // TODO: send driver requests
            sendDriverRequest($trip);
          }
        }
        else if ($time->lt(Carbon::now()->addMinutes(2))) {

          echo 'trip: '.$trip->id.' outdated\n';
          $trip->update(['did_run' => Carbon::now()]);

          $cancel_reason = 'Cancelled due its outdated.';

          $user = $trip->user;

          // cancel the trip due to the user already has a trip in the same time
          $trip->update(['status' => 'cancelled', 'cancel_reason' => $cancel_reason]);

          $trip->updateFirestore([
                                   ['path' => 'status', 'value' => 'cancelled']
                                 ]);

          $this->notifyFCMBackground($user->device_id,
                                     Lang::get('mobile.notifications.outdated_scheduled_trip.title'),
                                     Lang::get('mobile.notifications.outdated_scheduled_trip.description')
          );
        }

      } catch (\Exception $exception) {

        Log::error('------ scheduled error:'.$exception->getMessage());
      }

    }
  }
  private function sendNotification(array $devices, array $data) {
    $x = (new Fcm('AAAAl6IPWfM:APA91bFpwiTJ7z2jXzSgSc5xp7EK4JhrSk7wW5Fdw37d0ReQl7pvrn0zkbPINOpBxeSq5hXv5uqMJIe8P9q_dAz34zwnUUiZrmJfDGB_BHsZmyK1hpeG5SCXRJbBoin4y9tvab2X1kWm'))
      ->to($devices)
      ->priority('high')
      ->data($data)
      ->notification($data)
      ->send();

    \Illuminate\Support\Facades\Log::info('notify:: '.json_encode($x));
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
                                             'tag' => 'trip',
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
