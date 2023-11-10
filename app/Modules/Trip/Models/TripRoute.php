<?php

namespace App\Modules\Trip\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripRoute extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_trip_routes';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'trip_id',
    'enc',
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
}
