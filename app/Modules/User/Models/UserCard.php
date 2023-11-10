<?php

namespace App\Modules\User\Models;

use App\Support\Traits\UsesUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCard extends Model
{
  use UsesUUID;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_cards';

  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = false;

  protected $casts = [
    'id' => 'string'
  ];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'wallet_id',
    'number',
    'exp_month',
    'exp_year',
    'cvc',
    'stripe_method_id',
    'wallet_type',
    'image',
  ];

  /**
   * Associated Wallet.
   *
   * @return BelongsTo
   */
  public function wallet()
  {
    return $this->belongsTo(UserWallet::class, 'wallet_id');
  }

  /**
   * Protect card number.
   *
   * @return string
   */
  public function getProtectedNumberAttribute ()
  {
    return str_repeat('*', strlen($this -> attributes['number']) - 4) . substr($this -> attributes['number'], -4);
  }

  /**
   * Owner.
   *
   * @return BelongsTo
   */
  public function user ()
  {
    return $this -> belongsTo(User::class, 'user_id');
  }
}
