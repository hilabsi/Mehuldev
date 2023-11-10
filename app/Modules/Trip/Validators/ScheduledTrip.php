<?php

namespace App\Modules\Trip\Validators;

use App\Support\Contracts\ValidateModel;

class ScheduledTrip implements ValidateModel
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
      'wallet_type'  => 'required|in:business,regular',

      'path' => 'required',
      'date'    => 'required|date_format:Y-m-d-H-i-s'
    ];
  }
}
