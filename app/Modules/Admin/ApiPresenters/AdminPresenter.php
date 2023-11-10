<?php

namespace App\Modules\Admin\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class AdminPresenter extends ApisPresenter
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
      'email'     => $item -> email,
      'name'      => $item -> name,
      'role'      => [
        'id'    => $item -> role -> id,
        'name'  => $item -> role -> name
      ],
      'country'       => [
        'id'  => $item->country->id,
        'name'=> $item->country->name
      ],
      'is_active' => $item->is_active,
      'is_deleted'=> $item->is_active,
      'country_id'=> $item->country_id,
      'created_at'=> $item -> created_at,
      'deleted_at'=> $item -> deleted_at,
    ];
  }

  public function profile ($item)
  {
    return [
      'id'      => $item -> id,
      'name'    => $item -> name,
      'email'   => $item -> email,
      'country_id'=> $item->country_id,
      'role'    => [
        'id'    => $item -> role -> id,
        'name'  => $item -> role -> name,
        'permission'=> $item-> role ->rolepermission->map(function ($item) {
        return [
          'module_u_id' => $item->module_id,
          'name'   => $item->module->name,
          'alias_name'   => $item->module->alias_name,
          'view'   => $item->view_access,
          'add'   => $item->add_access,
          'update'   => $item->update_access,
          'delete'   => $item->delete_access,
          'status'   => $item->status_access,
        ];
      }),
      ],
      'country'       => [
        'id'  => $item->country->id,
        'name'=> $item->country->name
      ],
    ];
  }
}
