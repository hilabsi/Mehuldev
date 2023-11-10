<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverPasswordReset extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_driver_password_resets';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'driver_id',
    'email',
    'code',
    'status',
  ];

  /**
   * Owner.
   *
   * @return BelongsTo
   */
  public function driver ()
  {
    return $this -> belongsTo(Driver::class, 'driver_id');
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
