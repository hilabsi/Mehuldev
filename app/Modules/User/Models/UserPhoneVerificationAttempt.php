<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPhoneVerificationAttempt extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'l_user_phone_verification_attempts';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'phone',
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
}
