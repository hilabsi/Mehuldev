<?php

namespace App\Modules\Support\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class SupportCategoryPresenter extends ApisPresenter
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
      'type'        => $item -> type,
      'name'        => $item -> name,
      'language_id' => $item -> language_id,
      'created_at'  => $item->created_at->format('Y.m.d H:i:s')
    ];
  }
}
