<?php

namespace App\Modules\Car\Validators;

use App\Support\Contracts\ValidateModel;

class Car implements ValidateModel
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
      // TODO
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
      // TODO
    ];
  }

  public function chooseCar()
  {
    return [
      'car_id' => 'required|exists:d_cars,id',
    ];
  }

  public function changeSessionStatus()
  {
    return [
      'status' => 'required|in:online,offline'
    ];
  }
}
