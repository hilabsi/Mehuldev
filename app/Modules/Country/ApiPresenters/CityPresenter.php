<?php

namespace App\Modules\Country\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class CityPresenter extends ApisPresenter
{

  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present(): array
  {
    return $this->collection->map(
      function ($item) {
        return $this->item($item);
      }
    )->toArray();
  }

  public function item($item)
  {
    return [
      'id'        => $item->id,
      'name'      => $item->name,
      'country'   => [
        'id'    => $item->country->id,
        'name'  => $item->country->name,
      ],
      'status' => $item->status,
      'lat' => $item->lat,
      'lng' => $item->lng
    ];
  }
}
