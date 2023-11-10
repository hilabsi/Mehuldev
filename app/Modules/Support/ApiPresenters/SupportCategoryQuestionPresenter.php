<?php

namespace App\Modules\Support\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class SupportCategoryQuestionPresenter extends ApisPresenter
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
      'id'          => $item->id,
      'type'        => $item->type,
      'name_ar'     => $item->name_ar,
      'name_en'     => $item->name_en,
      'name_de'     => $item->name_de,
      'name'        => $item->name,
      'link'        => $item->link,
      'action'      => $item->action,
      'text'        => $item->text,
      'enable_help' => $item->enable_help,
      'category_id' => $item->category_id,
      'created_at'  => $item->created_at->format('Y.m.d H:i:s')
    ];
  }
}
