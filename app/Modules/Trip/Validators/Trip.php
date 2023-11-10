<?php

namespace App\Modules\Trip\Validators;

use App\Support\Contracts\ValidateModel;

class Trip implements ValidateModel
{
  /**
   * Validate model on edit operation.
   *
   * @param $id
   *
   * @return array
   */
  public function edit ($id): array
  {
    return [
      //
    ];
  }

  /**
   * Validate model on create operation.
   *
   * @return array
   */
  public function create (): array
  {
    return [
      //
    ];
  }

  public function search()
  {
    return [
      'source'              => 'required',
      'source.address'      => 'required|max:191',
      'source.location.lat' => 'required|numeric',
      'source.location.lng' => 'required|numeric',

      'destination'              => 'required',
      'destination.address'      => 'required|max:191',
      'destination.location.lat' => 'required|numeric',
      'destination.location.lng' => 'required|numeric',

      'stops'                 => 'sometimes',
      'stops.*.address'       => 'required|max:191',
      'stops.*.location'      => 'required',
      'stops.*.location.lat'  => 'required|numeric',
      'stops.*.location.lng'  => 'required|numeric',

      'pickup'          => 'required',
      'pickup.address'  => 'required',
      'pickup.location.lat'      => 'required|numeric',
      'pickup.location.lng'      => 'required|numeric',

      'category_id' => 'required|numeric|exists:d_car_categories,id',
      'place_id'    => 'nullable|exists:d_user_places,id',
      'wallet_type' => 'required|in:business,regular',

      'path'        => 'required',
    ];
  }

  public function carsPrices()
  {
    return [
      'distance'    => 'required|numeric',
      'wallet_type' => 'required|in:regular,business',
      'duration'        => 'required|numeric',
    ];
  }

  public function carsPricesWithPickup()
  {
    return [
      'distance'    => 'required|numeric',
      'category_id' => 'required|exists:d_car_categories,id',
      'wallet_type' => 'required|in:regular,business',
      'duration'        => 'required|numeric',
    ];
  }

  /**
   * @return string[]
   */
  public function rateTrip(): array
  {
    return [
      'trip_id' => 'required|exists:d_trips,id',
      'rating'  => 'required|numeric|max:5|min:1',
      'comment' => 'nullable|max:191'
    ];
  }
  /**
   * @return string[]
   */
  public function cancelTrip(): array
  {
    return [
      'trip_id' => 'required|exists:d_trips,id',
      'cancel_reason'  => 'required|max:191'
    ];
  }

  public function sendMessage(): array
  {
    return [
      'trip_id' => 'required|exists:d_trips,id',
      'message' => 'required|max:191',
    ];
  }

  public function referralCode()
  {
    return [

    ];
  }
}
