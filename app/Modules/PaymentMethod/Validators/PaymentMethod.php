<?php

namespace App\Modules\PaymentMethod\Validators;

use App\Support\Contracts\ValidateModel;
use Carbon\Carbon;
use LVR\CreditCard\CardNumber;

class PaymentMethod implements ValidateModel
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
      'name'  => 'sometimes|required|max:191|unique:s_payment_methods,name,'.$id,
      'status'=> 'sometimes|required|in:enabled,disabled',
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
      'name'  => 'sometimes|required|max:191|unique:s_payment_methods,name',
    ];
  }

  public function addCard()
  {
    return [
      'number'    => 'required|numeric|digits_between:7,19',
      'exp_month' => 'required|numeric|min:1|max:12',
      'exp_year'  => 'required|numeric',
      'cvc'       => 'required|digits_between:3,4',
    ];
  }

  public function updateDefault($userId)
  {
    return [
      'type'        => 'required|in:cash,card,apple,google',
      'card_id'     => 'required_if:type,card',
    ];
  }

  public function removeCard($userId)
  {
    return [
      'card_id'     => 'required_if:type,card'
    ];
  }
}
