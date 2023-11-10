<?php

namespace App\Modules\Settings\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class OnBoardingSlidePresenter extends ApisPresenter
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
    return [
      'order'       => $item->order,
      'title'       => $item->title,
      'image'       => $item->image,
      'description' => $item->description
    ];
  }
}
