<?php

namespace App\Jobs;

use App\Modules\Driver\Models\Driver;
use Google\Cloud\Core\GeoPoint;
use GPBMetadata\Google\Firestore\Admin\V1\Location;
use Illuminate\Contracts\Queue\ShouldQueue;

class MockingTrip3 extends Job implements ShouldQueue
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
                                   ['path' => 'status', 'value' => 'pickup'],
                                   ['path' => 'has_driver', 'value' => true],
                                   ['path' => 'driver' , 'value' => [
                                     'first_name' =>  'Stephan',
                                     'last_name'  =>  'Markovic',
                                     'image'      => 'https://w7.pngwing.com/pngs/7/618/png-transparent-man-illustration-avatar-icon-fashion-men-avatar-face-fashion-girl-heroes-thumbnail.png',
                                     'rating'     =>  3.2,
                                     'location'=> ['U' => $this->trip->source_location->getLat() + 0.01, 'k' => $this->trip->source_location->getLng()+ 0.01],
                                     'car'  =>  [
                                       'type'     => 'KIA Rio',
                                       'number'   => 'AV 143',
                                       'color'    => 'black',
                                       'image'    => 'https://www.iconpacks.net/icons/1/free-car-icon-1057-thumb.png',
                                     ],
                                   ]],
                                 ]);

  }
}
