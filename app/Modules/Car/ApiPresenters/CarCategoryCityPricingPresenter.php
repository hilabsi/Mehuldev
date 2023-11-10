<?php

namespace App\Modules\Car\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class CarCategoryCityPricingPresenter extends ApisPresenter
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
      'id'                => $item->id,
      'country_id'        => $item->country_id,
      'from_city'         => $item->from_city,
      'to_city'           => $item->to_city,
      'start_price'       => $item->start_price,
      'km_price'          => $item->km_price,
      'minimum_price'     => $item->minimum_price,
      'minute_price'      => $item->minute_price,
      'range_percent'     => $item->range_percent,
      'factor'            => $item->factor,
      'free_wait_time'    => $item->free_wait_time,
      'wait_minute_price' => $item->wait_minute_price,
      'type'              => $item->category->type,
      'formatted_type'    => $item->category->type === 'auto' ? 'Auto Calculated' : 'Self Calculated',
      'created_at'        => $item->created_at,
    ];
  }
}
