<?php

namespace App\Modules\CustomPage\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class CustomPagePresenter extends ApisPresenter
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

  public function format($item)
  {
    return [
      'content' => $item->content,
      'language_id' => $item->language_id,
      'id'      => $item->id,
    ];
  }

  public function item ($item)
  {
    return $item->content;
  }
}
