<?php

namespace App\Modules\Invoice\Validators;

use App\Support\Contracts\ValidateModel;

class Invoice implements ValidateModel
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
      'name'    => 'sometimes|required|max:191',
      'email'   => 'sometimes|required|max:191|email|unique:d_admins,email,'.$id,
      'role_id' => 'sometimes|required|exists:d_admin_roles,id',
      'password'=> 'sometimes|required|max:16|min:6',
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
      'name'    => 'required|max:191',
      'email'   => 'required|max:191|email|unique:d_admins,email',
      'role_id' => 'required|exists:d_admin_roles,id',
      'password'=> 'required|max:16|min:6',
    ];
  }
}
