<?php

namespace App\Jobs;

use App\Modules\Driver\Models\Driver;
use Illuminate\Contracts\Queue\ShouldQueue;

class MockingTrip1 extends Job implements ShouldQueue
{
  protected $trip;

  /**
   * The number of seconds the job can run before timing out.
   *
   * @var int
   */
  public $timeout = 120;
  public $delay = 30;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($trip)
  {
    $this->trip = $trip;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
//    $this->trip->setDriver(Driver::first());

    $this->trip->updateFirestore([
                                   ['path' => 'status', 'value' => 'started'],
                                 ]);

  }
}
