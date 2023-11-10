<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPasswordReset extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_password_resets';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'email',
    'code',
    'status',
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
   * Filter all non-pending requests
   *
   * @param $query
   * @return mixed
   */
  public function scopePending($query)
  {
    return $query->where('status', 'pending');
  }
}
