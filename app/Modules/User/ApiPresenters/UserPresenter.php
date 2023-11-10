<?php

namespace App\Modules\User\ApiPresenters;

use App\Modules\Trip\Models\Trip;
use App\Support\Contracts\ApisPresenter;
use Carbon\Carbon;

class UserPresenter extends ApisPresenter
{
  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present (): array
  {
    return $this -> collection -> map(function ($item) {
      return $this -> item($item);
    })-> toArray();
  }

  public function item ($item): array
  {
    $trips = Trip::whereUserId($item->id);
    return [
      'id'            => $item -> id,
      'name'    => $item -> full_name,
      'first_name'    => $item -> first_name,
      'last_name'     => $item -> last_name,
      'verified'      => $item -> is_phone_verified,
      'is_verified'      => $item -> is_phone_verified,
      'active'        => $item -> is_active,
      'email'         => $item -> email,
      'phone'         => $item -> getFullPhoneNumber(),
      'plain_phone'         => $item -> phone,
      'fcmable'     => $item->hasDeviceId(),
      'created_at'    => $item -> created_at,
      'rating'        => $item->avgRating(),
      'trips'         => $trips->count(),
      'country'       => [
        'id'  => $item->country->id,
        'name'=> $item->country->name
      ],
      'company_address' => $item->business ? $item->business->company_address : null,
      'uid'             => $item->business ? $item->business->uid : null,
      'company_name'    => $item->business ? $item->business->company_name : null,
      'company_email'   => $item->business ? $item->business->email : null,
      'cancelled_trips' => $trips->whereStatus('cancelled')->count(),
      'business' => $item->business ? [
        'company_address' => $item->business->company_address,
        'uid'             => $item->business->uid,
        'company_name'    => $item->business->company_name,
        'email'           => $item->business->email
      ]: null
    ];
  }

  public function shortPresent($item)
  {
    return [
      'id'            => $item -> id,
      'first_name'    => $item -> first_name,
      'name'    => $item -> full_name,
      'last_name'     => $item -> last_name,
      'verified'      => $item -> is_phone_verified,
      'active'        => $item -> is_active,
      'email'         => $item -> email,
      'phone'         => $item -> getFullPhoneNumber(),
      'created_at'    => $item -> created_at,
      'rating'        => $item->avgRating(),
    ];
  }
}
