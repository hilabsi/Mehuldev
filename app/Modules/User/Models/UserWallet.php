<?php

namespace App\Modules\User\Models;

use App\Modules\Coupon\Models\Coupon;
use App\Support\Traits\UsesUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWallet extends Model
{
  use UsesUUID;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_wallets';

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
    'type',
    'default_payment_type',
    'card_id',
    'balance',
    'active_coupon_id',
    'user_id',
  ];

  /**
   * Associated cards.
   *
   * @return HasMany
   */
  public function cards()
  {
    return $this->hasMany(UserCard::class, 'wallet_id');
  }

  public function coupons()
  {
   return $this->hasMany(UserCoupon::class, 'wallet_id');
  }

  public function activeCoupon()
  {
    return $this->belongsTo(Coupon::class, 'active_coupon_id');
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
