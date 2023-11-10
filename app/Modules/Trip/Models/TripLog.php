<?php

namespace App\Modules\Trip\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripLog extends Model
{
  use SpatialTrait;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_trip_logs';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'trip_id',
    'type',
    'user_location',
    'driver_location',
  ];

  /**
   * The attributes that are spatial fields.
   *
   * @var array
   */
  protected array $spatialFields = [
    'driver_location',
    'user_location',
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
