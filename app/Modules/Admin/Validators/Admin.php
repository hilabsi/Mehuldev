<?php

namespace App\Modules\Admin\Validators;

use App\Support\Contracts\ValidateModel;

class Admin implements ValidateModel
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
      'email'   => 'sometimes|required|max:191|email',
      'role_id' => 'sometimes|required|exists:d_admin_roles,id',
      'country_id' => 'sometimes|required',
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
      'email'   => 'required|max:191|email',
      'role_id' => 'required|exists:d_admin_roles,id',
      'country_id' => 'required',
      'password'=> 'required|max:16|min:6',
    ];
  }
}
