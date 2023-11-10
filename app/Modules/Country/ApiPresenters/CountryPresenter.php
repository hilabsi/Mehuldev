<?php

namespace App\Modules\Country\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class CountryPresenter extends ApisPresenter
{
  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present(): array
  {
    return $this->collection->map(function ($item) {
      return $this->item($item);
    })->toArray();
  }

  public function item($item)
  {
    return [
      'id'        => $item->id,
      'name'      => $item->name,
      'prefix'    => $item->phone_prefix,
      'status'    => $item->status,
      'alpha2'    => $item->alpha2,
      'alpha3'    => $item->alpha3,
      'icon'      => $item->icon
    ];
  }

  public function mobile($item)
  {
    return [
      'id'          => $item->id,
      'name'        => $item->name,
      'callingCode' => [str_replace('+', '', $item->phone_prefix)],
      'cca2'        => $item->alpha2,
      'flag'        => 'flag-'.strtolower($item->alpha2),
    ];
  }
}
