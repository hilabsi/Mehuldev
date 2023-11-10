<?php

namespace App\Modules\Campaign\Validators;

use App\Support\Contracts\ValidateModel;

class EmailTemplate implements ValidateModel
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
      'title'     => 'required|max:191',
      'template'  => 'required',
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
      'title'     => 'required|max:191',
      'template'  => 'required',
    ];
  }
}
