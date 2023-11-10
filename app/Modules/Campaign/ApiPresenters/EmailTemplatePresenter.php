<?php

namespace App\Modules\Campaign\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class EmailTemplatePresenter extends ApisPresenter
{

  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present (): array
  {
    return $this -> collection -> map(function ($item) {
      return $this -> item($item);
    })->toArray();
  }

  public function item ($item)
  {
    return [
      'id'        => $item -> id,
      'title'     => $item -> title,
      'template'  => $item -> template,
    ];
  }
}
