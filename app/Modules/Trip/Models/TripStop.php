<?php

namespace App\Modules\Trip\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripStop extends Model
{
  use SpatialTrait;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_trip_stops';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'trip_id',
    'address',
    'location',
    'order',
    'reached_at',
  ];

  protected $casts = [
    'reached_at' => 'timestamp'
  ];

  protected $spatialFields = [
    'location'
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
