<?php

namespace App\Modules\CustomPage\Validators;

use App\Support\Contracts\ValidateModel;

class CustomPage implements ValidateModel
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
      //
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
      //
    ];
  }

  public function mobilePage()
  {
    return [
      'page' => 'required|in:about,privacy,impressum',
    ];
  }
}
