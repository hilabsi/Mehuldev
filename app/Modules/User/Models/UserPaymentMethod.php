<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_payment_methods';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'payment_method_id',
    'token',
    'card_number',
    'cvc',
    'exp_date',
  ];

  /**
   * Owner.
   *
   * @return BelongsTo
   */
  public function user ()
  {
    return $this -> belongsTo(User::class, 'user_id');
  }

  /**
   * Related payment method.
   *
   * @return BelongsTo
   */
  public function paymentMethod()
  {
    return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
  }
}
