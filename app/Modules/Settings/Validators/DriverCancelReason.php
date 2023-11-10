<?php

namespace App\Modules\Settings\Validators;

use App\Support\Contracts\ValidateModel;

class DriverCancelReason implements ValidateModel
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
      'reason'          => 'sometimes|required|max:191',
      'language_id'          => 'sometimes|required|exists:s_languages,id',
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
      'reason'          => 'required|max:191',
      'language_id'     => 'required|exists:s_languages,id',
    ];
  }
}
