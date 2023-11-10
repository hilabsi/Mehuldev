<?php

namespace App\Modules\Settings\Validators;

use App\Support\Contracts\ValidateModel;

class CarBrand implements ValidateModel
{
  /**
   * Validate model on edit operation.
   *
   * @param $id
   *
   * @return array
   */
  public function edit ($id): array
  {
    return [
      'title' => 'sometimes|required|max:191',
      'code'  => 'sometimes|required|max:191',
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
      'title' => 'required|max:191',
      'code'  => 'sometimes|required|nullable|max:191',
    ];
  }
}
