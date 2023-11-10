<?php

namespace App\Modules\Settings\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class CarModelPresenter extends ApisPresenter
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
      'title'     => $item -> title,
      'code'     => $item -> code,
      'brand_id'     => $item -> brand_id,
      'brand'     => [
        'id' => $item -> brand->id,
        'title' => $item -> brand->title,
      ],
      'created_at'  => $item->created_at->format('Y.m.d H:i:s')
    ];
  }
}
