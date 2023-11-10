<?php

namespace App\Modules\Language\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class LanguagePresenter extends ApisPresenter
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
    })
      -> toArray();
  }

  public function item ($item)
  {
    return [
      'id'        => $item -> id,
      'name'      => $item -> name,
      'status'    => $item -> status,
      'shortcut'  => $item -> shortcut,
      'rtl'       => $item -> rtl
    ];
  }

  public function mobile ($item)
  {
    return [
      'id'        => $item -> id,
      'name'      => $item -> name,
      'shortcut'  => $item -> shortcut,
      'rtl'       => $item -> rtl
    ];
  }
}
