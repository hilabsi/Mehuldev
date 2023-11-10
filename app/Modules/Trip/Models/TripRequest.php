<?php

namespace App\Modules\Trip\Models;

use App\Modules\Car\Models\CarCategory;
use App\Modules\Driver\Models\Driver;
use App\Support\Traits\UsesUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripRequest extends Model
{
  use UsesUUID;

  public $incrementing = false;

  protected $casts = [
    'id' => 'string',
  ];

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_trip_requests';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'trip_id',
    'distance',
    'driver_id',
    'category_id',
    'status',
  ];

  protected $with = ['trip', 'category'];

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
  /**
   * Associated Trip.
   *
   * @return BelongsTo
   */
  public function trip()
  {
    return $this->belongsTo(Trip::class, 'trip_id');
  }

  public function category()
  {
    return $this->belongsTo(CarCategory::class, 'category_id');
  }

}
