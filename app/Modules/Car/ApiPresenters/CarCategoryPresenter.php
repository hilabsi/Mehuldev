<?php

namespace App\Modules\Car\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class CarCategoryPresenter extends ApisPresenter
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
      'name'              => $item->name,
      'seats'             => $item->seats,
      'start_price'       => $item->start_price,
      'km_price'          => $item->km_price,
      'minimum_price'     => $item->minimum_price,
      'minute_price'      => $item->minute_price,
      'range_percent'     => $item->range_percent,
      'image'             => $item->image,
      'image_url'         => $item->image,
      'factor'            => $item->factor,
      'free_wait_time'    => $item->free_wait_time,
      'wait_minute_price' => $item->wait_minute_price,
      'status'            => $item->status,
      'type'              => $item->type,
      'formatted_type'    => $item->type === 'auto' ? 'Auto Calculated' : 'Self Calculated',
      'cars'              => $item->cars->map(function ($item) {
        return [
          'partner' => $item->partner->company_name,
          'model'   => $item->model->title,
          'brand'   => $item->brand->title,
          'year'    => $item->year,
          'lpn'     => $item->lpn,
          'status'  => $item->isBusy() ? 'busy' : 'available',
        ];
      }),
      'created_at'        => $item->created_at,
    ];
  }
}
