<?php

namespace App\Modules\Settings\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class PartnerDocumentPresenter extends ApisPresenter
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
      'field_name'     => $item -> field_name,
      'is_required'     => $item -> is_required,
      'created_at'  => $item->created_at->format('Y.m.d H:i:s')
    ];
  }
}
