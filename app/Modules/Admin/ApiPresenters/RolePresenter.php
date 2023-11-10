<?php

namespace App\Modules\Admin\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class RolePresenter extends ApisPresenter
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
      'totuser'   => $item -> getUserno(),
      'created_at'=> $item -> created_at
    ];
  }
  
  public function show ($item)
  {
    return [
      'id'        => $item -> id,
      'name'      => $item -> name,
      'totuser'   => $item -> getUserno(),
      'created_at'=> $item -> created_at,
      'permission'=> $item->rolepermission->map(function ($item) {
        return [
          'module_u_id' => $item->module_id,
          'name'   => $item->module->name,
          'view'   => $item->view_access,
          'add'   => $item->add_access,
          'update'   => $item->update_access,
          'delete'   => $item->delete_access,
          'status'   => $item->status_access,
        ];
      }),
    ];
  }
}
