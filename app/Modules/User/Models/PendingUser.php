<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingUser extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 't_pending_users';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'provider',
    'provider_id',
  ];
}
