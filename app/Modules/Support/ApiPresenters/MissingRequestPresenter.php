<?php

namespace App\Modules\Support\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class MissingRequestPresenter extends ApisPresenter
{

  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present (): array
  {
    return $this -> collection -> map(function ($item) {
      return $this->item($item);
    })-> toArray();
  }

  public function item ($item)
  {
    return [
      'id'          => $item -> id,
      'type'        => $item -> type,
      'status'      => $item -> status,
      'description' => $item -> description,
      'formatted_type' => implode(',', json_decode($item->type)),
      'trip_id'     => $item->trip_id,
      'user_id'     => $item->user_id,
      'user_name'   => $item->user->full_name,
      'user_fcmable'=> $item->user->hasDeviceId(),
      'created_at'  => $item->created_at->format('Y.m.d H:i:s')
    ];
  }
}
