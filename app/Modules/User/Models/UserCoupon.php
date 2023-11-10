<?php

namespace App\Modules\User\Models;

use App\Modules\Coupon\Models\Coupon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCoupon extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_coupons';

  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = false;

  /**
   * The attributes that should be cast.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'string'
  ];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'used_count',
    'user_id',
    'coupon_id',
    'max_usage',
    'code',
    'wallet_id',
    'wallet_type',
    'finished_at',
  ];

  /**
   * Associated coupon.
   *
   * @return BelongsTo
   */
  public function coupon()
  {
    return $this->belongsTo(Coupon::class, 'coupon_id');
  }

  public function scopeAvailable($query)
  {
    return $query->where('finished_at', '=', null);
  }

  /**
   * Associated wallet.
   *
   * @return BelongsTo
   */
  public function wallet()
  {
    return $this->belongsTo(UserWallet::class, 'wallet_id');
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
