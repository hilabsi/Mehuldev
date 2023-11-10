<?php

namespace App\Jobs;

use App\Modules\Driver\Models\Driver;
use Illuminate\Contracts\Queue\ShouldQueue;

class MockingTrip extends Job implements ShouldQueue
{
  protected $trip;
  public $delay = 30;

  /**
   * The number of seconds the job can run before timing out.
   *
   * @var int
   */
  public $timeout = 120;

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
                                   ['path' => 'status', 'value' => 'pickup'],
                                   ['path' => 'has_driver', 'value' => true],
                                   ['path' => 'driver' , 'value' => [
                                     'first_name' =>  'Stephan',
                                     'last_name'  =>  'Markovic',
                                     'rating'     =>  3.2,
                                     'car'  =>  [
                                       'type'     => 'KIA Rio',
                                       'number'   => 'AV 143',
                                       'color'    => 'black',
                                       'image'    => '',
                                     ],
                                   ]],
                                 ]);

      }
}
