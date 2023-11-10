<?php

namespace App\Modules\Admin\Validators;

use App\Support\Contracts\ValidateModel;

class AdminRole implements ValidateModel
{

  /**
   * Validate model on edit operation.
   *
   * @param
   *            $id
   *
   * @return array
   */
  public function edit ($id): array
  {
    return [
      'name' => 'required|max:191|unique:d_admin_roles,name,'.$id,
    ];
  }

  /**
   * Validate model on create operation.
   *
   * @return array
   */
  public function create (): array
  {
    return [
      'name' => 'required|max:191|unique:d_admin_roles,name',
    ];
  }
}
