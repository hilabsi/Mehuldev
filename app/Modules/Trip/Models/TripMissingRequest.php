<?php

namespace App\Modules\Trip\Models;

use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripMissingRequest extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_trip_missing_requests';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'trip_id',
    'type',
    'user_id',
    'description',
    'status',
  ];

  /**
   * Associated Trip.
   *
   * @return BelongsTo
   */
  public function trip()
  {
    return $this->belongsTo(Trip::class, 'trip_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
