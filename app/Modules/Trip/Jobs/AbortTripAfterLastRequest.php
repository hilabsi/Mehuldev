<?php

namespace App\Modules\Trip\Jobs;

use App\Jobs\Job;
use App\Modules\Trip\Models\TripRequest;

class AbortTripAfterLastRequest extends Job
{
  protected $trip;

  public $delay = 20;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($trip)
  {
    $this->trip = $trip;
    $this->delay = settings('driver_request_timeout') ?? 60;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    // check first if there is an active request and cancel it
    $currentRequest = TripRequest::whereTripId($this->trip->id)->whereStatus('pending')->first();

    if ($currentRequest) {
      $currentRequest->update(['status' => 'ignored']);

      $currentRequest->driver->updateFirestore(
        [
          ['path' => 'request', 'value' => null]
        ]
      );
    }

    $this->trip->update([
                          'status' => 'aborted',
                        ]);

    $this->trip->updateFirestore([
                                   ['path' => 'search_failed', 'value' => true],
                                   ['path' => 'status', 'value' => 'aborted'],
                                 ]);

    $this->trip->user->update([
                                'current_trip' => null
                              ]);

    $this->trip->user->updateFirestore([
                                         ['path' => 'current_trip', 'value' => null]
                                       ]);
  }
}
