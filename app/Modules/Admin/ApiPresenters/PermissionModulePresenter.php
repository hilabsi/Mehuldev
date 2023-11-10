<?php

namespace App\Modules\Admin\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class PermissionModulePresenter extends ApisPresenter
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
    })-> toArray();
  }

  public function item ($item)
  {
    return [
      'id'        => $item -> id,
      'name'      => $item -> name,
      'alias_name'=> $item -> alias_name
    ];
  }
}
