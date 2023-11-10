<?php

namespace App\Modules\Trip\Jobs;

use App\Jobs\Job;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;

class SaveTripRouteImage extends Job
{
  protected $trip;

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
    $guzzle = new Client();

    $markers = [];
    foreach ($this->trip->stops as $stop)
      $markers[] = $stop->location->getLat().','.$stop->location->getLng();

    $response = $guzzle->get("https://maps.googleapis.com/maps/api/staticmap?"
                             . "map_id=f1399d533e05cfe8&path=color:0x000000|enc:".$this->trip->getEnc()
                             . "&markers=anchor:center|icon:".urlencode("https://lobi.s3.eu-central-1.amazonaws.com/resources/car.png")."|".$this->trip->pickup_location->getLat().','.$this->trip->pickup_location->getLng()
                             . "&markers=icon:".urlencode("http://lobi.s3.eu-central-1.amazonaws.com/resources/pin.png")."|".$this->trip->destination_location->getLat().','.$this->trip->destination_location->getLng()
                             . (count($markers) ? implode('|', $markers) : '')
                             . "&key=AIzaSyCf8wp1RbL0ReAudu3AaNbPwvL5ugR4xgk&size=380x210");

    Storage::disk('s3')->put('trips/'.$this->trip->id.'/route.png', $response->getBody());

    $this->trip->update(['route_image' => s3('trips/'.$this->trip->id.'/route.png')]);

    $this->trip->updateFirestore([
                                   ['path' => 'route_image', 'value' =>  s3('trips/'.$this->trip->id.'/route.png')]
                                 ]);
  }
}
