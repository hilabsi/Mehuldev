<?php

namespace App\Modules\Coupon\Models;

use App\Support\Traits\ModelDefaults;
use App\Support\Traits\UsesUUID;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Coupon\Validators\Coupon as Validator;

class Coupon extends Model implements HasValidations
{
  use UsesUUID;
  use ModelDefaults;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_coupons';

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
    'name',
    'expiring_at',
    'description',
    'amount',
    'quantity',
    'amount_type',
    'used_count',
    'code',
    'max_usage_per_user',
  ];

  /**
   * Gets model's operations' validation roles.
   *
   * @return ValidateModel
   */
  public static function validations (): ValidateModel
  {
    return new Validator();
  }
}
