<?php

namespace App\Modules\Coupon\Validators;

use App\Support\Contracts\ValidateModel;

class Coupon implements ValidateModel
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
      'name'        => 'sometimes|required|max:191',
      'expiring_at' => 'sometimes|required|date_format:Y-m-d',
      'description' => 'sometimes|required|max:191',
      'amount'      => 'sometimes|required|numeric|min:1|max:99',
      'quantity'    => 'sometimes|required|numeric|min:1|max:10000',
      'amount_type' => 'sometimes|required|in:percent,amount',
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
      'name'        => 'required|max:191',
      'expiring_at' => 'required|date_format:Y-m-d',
      'description' => 'required|max:191',
      'amount'      => 'required|numeric|min:1|max:99',
      'quantity'    => 'required|numeric|min:1|max:10000',
      'amount_type' => 'required|in:percent,amount',
    ];
  }

  public function addCoupon()
  {
    return [
      'code' => 'required|max:6',
    ];
  }

  public function toggleActive()
  {
    return [
      'coupon_id' => 'required|exists:d_coupons,id',
      'distance' => 'required|numeric',
      'duration' => 'required|numeric',
    ];
  }
}
