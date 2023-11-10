<?php

namespace App\Modules\Settings\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class DriverCancelReasonPresenter extends ApisPresenter
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
    })->toArray();
  }

  public function item ($item)
  {
    return [
      'id'          => $item -> id,
      'language_id' => $item -> language_id,
      'reason'      => $item -> reason,
      'created_at'  => $item->created_at->format('Y.m.d H:i:s')
    ];
  }
}
