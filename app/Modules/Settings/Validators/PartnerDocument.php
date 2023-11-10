<?php

namespace App\Modules\Settings\Validators;

use App\Support\Contracts\ValidateModel;

class PartnerDocument implements ValidateModel
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
      'field_name' => 'sometimes|required|max:191',
      'is_required'  => 'sometimes',
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
      'field_name' => 'required|max:191',
    ];
  }
}
