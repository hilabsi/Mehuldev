<?php

namespace App\Modules\Trip\ApiPresenters;

use App\Modules\Driver\Models\DriverRating;
use App\Modules\Trip\Models\TripRequest;
use App\Modules\User\ApiPresenters\UserPresenter;
use App\Support\Contracts\ApisPresenter;
use Carbon\Carbon;

class TripPresenter extends ApisPresenter
{
  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present (): array
  {
    return $this->collection->map(function ($item) {
      return $this->item($item);
    })-> toArray();
  }

  public function item ($item)
  {
    $timezone = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, strtoupper($item->user->country->alpha2))[0];

    return [
      'id'                  => $item -> id,
      'source_location'     => $item->pickup_location,
      'destination_location'=> $item->destination_location,
      'source_address'      => $item->pickup_address,
      'destination_address' => $item->destination_address,
      'has_stops'           => !!$item->has_stops,
      'has_driver'          => !!$item->driver,
      'stops'               => $item->stops->map(function ($item) {
        return [
          'address' => $item->address,
          'location'=> $item->location,
          'order'   => $item->order,
        ];
      }),
      'created_at'  => $item->created_at->timezone($timezone)->timestamp,
      'car_model'   => ($item->car ? getCarModel($item->car) : __('label.mobile.not_associated')). ' - '.$item->status,
      'cost'        => formatNumber($item->cost + $item->wait_time_cost),
      'rating'      => ($rate = DriverRating::whereTripId($item->id)->first()) ? $rate->rating : 0,
      'image'       => $item->route_image,
      'driver' => [
        'name'  => $item->driver ? $item->driver->name : __('label.mobile.not_associated'),
        'image' => $item->driver ? $item->driver->picture : null,
      ]
    ];
  }

  public function overview ($item)
  {
    $timezone = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, strtoupper($item->user->country->alpha2))[0];
    $tripRequest = TripRequest::whereTripId($item->id)->whereStatus('accepted')->first();
    return [
      'id'                  => $item -> id,
      'source_location'     => $item->pickup_location,
      'destination_location'=> $item->destination_location,
      'source_address'      => $item->pickup_address,
      'destination_address' => $item->destination_address,
      'has_stops'           => !!$item->has_stops,
      'has_driver'          => !!$item->driver,
      'user'                => (new UserPresenter())->item($item->user),
      'stops'               => $item->stops->map(function ($item) use ($timezone) {
        return [
          'address' => $item->address,
          'location'=> $item->location,
          'order'   => $item->order,
          'reached_at' => $item->reached_at ? $item->reached_at->timezone($timezone)->format('Y.m.d H:i:s') : '-',
        ];
      }),
      'ended_at'    => $item->ended_at ? Carbon::createFromTimestamp($item->ended_at)->timezone($timezone)->format('Y.m.d H:i:s') : '-',
      'accepted_at' => $tripRequest ? $tripRequest->created_at->timezone($timezone)->format('Y.m.d H:i:s') : '-',
      'started_at'  => $item->started_at? Carbon::createFromTimestamp($item->started_at)->timezone($timezone)->format('Y.m.d H:i:s') : '-',
      'created_at'  => $item->created_at->timezone($timezone)->timestamp,
      'formatted_created_at' => $item->created_at->timezone($timezone)->format('Y.m.d H:i:s'),
      'car_model'   => ($item->car ? getCarModel($item->car) : __('label.mobile.not_associated')). ' - '.$item->status,
      'cost'        => formatNumber($item->cost + $item->wait_time_cost),
      'rating'      => ($rate = DriverRating::whereTripId($item->id)->first()) ? $rate->rating : 0,
      'image'       => $item->route_image,
      'driver' => [
        'name'  => $item->driver ? $item->driver->name : __('label.mobile.not_associated'),
        'image' => $item->driver ? $item->driver->picture : null,
      ]
    ];
  }

  public function scheduled ($item)
  {
    $timezone = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, strtoupper($item->user->country->alpha2))[0];

    return [
      'id'                  => $item -> id,
      'source_location'     => $item->pickup_location,
      'destination_location'=> $item->destination_location,
      'source_address'      => $item->pickup_address,
      'destination_address' => $item->destination_address,
      'has_stops'           => !!$item->has_stops,
      'has_driver'          => !!$item->driver,
      'stops'               => $item->stops->map(function ($item) {
        return [
          'address' => $item->address,
          'location'=> $item->location,
          'order'   => $item->order,
        ];
      }),
      'scheduled_on' => Carbon::createFromFormat('Y-m-d-H-i-s', $item->scheduled_on)->timezone($timezone)->format('Y-m-d-H-i-s'),
      'category'    => $item->category->name,
      'created_at'  => $item->created_at->timezone($timezone)->timestamp,
      'car_model'   => $item->car ? getCarModel($item->car) : __('label.mobile.not_associated'),
      'cost'        => formatNumber($item->cost + $item->wait_time_cost),
      'rating'      => ($rate = DriverRating::whereTripId($item->id)->first()) ? $rate->rating : 0,
      'image'       => $item->route_image,
      'driver' => [
        'name'  => $item->driver ? $item->driver->name : __('label.mobile.not_associated'),
        'image' => $item->driver ? $item->driver->picture : null,
      ]
    ];
  }
}
