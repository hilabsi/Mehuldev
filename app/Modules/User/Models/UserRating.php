<?php

namespace App\Modules\User\Models;

use App\Modules\Trip\Models\Trip;
use App\Modules\Driver\Models\Driver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRating extends Model
{
  use Notifiable;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_ratings';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'driver_id',
    'trip_id',
    'user_id',
    'rating',
    'comment',
  ];

  /**
   * @return BelongsTo
   */
  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }

  /**
   * @return BelongsTo
   */
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * @return BelongsTo
   */
  public function trip()
  {
    return $this->belongsTo(Trip::class, 'trip_id');
  }
}
