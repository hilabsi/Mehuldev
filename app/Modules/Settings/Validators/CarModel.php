<?php

namespace App\Modules\Settings\Validators;

use App\Support\Contracts\ValidateModel;

class CarModel implements ValidateModel
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
      'title'     => 'sometimes|required|max:191',
      'brand_id'  => 'sometimes|required|exists:s_car_brands,id',
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
      'brand_id'  => 'required|exists:s_car_brands,id',
    ];
  }
}
