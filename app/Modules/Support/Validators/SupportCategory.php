<?php

namespace App\Modules\Support\Validators;

use App\Support\Contracts\ValidateModel;

class SupportCategory implements ValidateModel
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
      'name'          => 'sometimes|required|max:191',
      'type'             => 'sometimes|required|in:driver,user'
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
      'name'          => 'required|max:191',
      'type'             => 'required|in:driver,user'
    ];
  }
}
