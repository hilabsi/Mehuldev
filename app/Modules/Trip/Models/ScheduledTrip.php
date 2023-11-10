<?php

namespace App\Modules\Trip\Models;

use App\Services\FireStoreService;
use App\Support\Traits\UsesUUID;
use App\Modules\User\Models\User;
use App\Modules\User\Models\UserCard;
use Google\Cloud\Core\GeoPoint;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use App\Modules\Trip\Validators\ScheduledTrip as Validator;

class ScheduledTrip extends Model implements HasValidations
{
  use SpatialTrait;
  use UsesUUID;

  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = false;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_scheduled_trips';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'source_address',
    'source_location',
    'pickup_address',
    'pickup_location',
    'destination_address',
    'destination_location',
    'status',
    'firestore_ref',
    'user_id',
    'has_stops',
    'wallet_type',
    'payment_type',
    'card_id',
    'cost',
    'place_id',
    'date',
    'scheduled_on'
  ];

  /**
   * The attributes that are spatial fields.
   *
   * @var array
   */
  protected array $spatialFields = [
    'source_location',
    'pickup_location',
    'destination_location',
  ];

  public function stops()
  {
    return $this->hasMany(TripStop::class, 'trip_id');
  }

  public function card()
  {
    return $this->belongsTo(UserCard::class, 'card_id');
  }

  public function setSourceLocationAttribute(?array $value)
  {
    $this->attributes['source_location'] = new Point($value['lat'], $value['lng'], 4326);
  }

  public function setDestinationLocationAttribute(?array $value)
  {
    $this->attributes['destination_location'] = new Point($value['lat'], $value['lng'], 4326);
  }

  public function setPickupLocationAttribute(?array $value)
  {
    $this->attributes['pickup_location'] = new Point($value['lat'], $value['lng'], 4326);
  }

  /**
   * update trips stops.
   *
   * @param  array  $stops
   */
  public function setStops(array $stops)
  {
    foreach ($stops as $stop)
      $this->stops()->create($stop);
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Gets model's operations' validation roles.
   *
   * @return ValidateModel
   */
  public static function validations (): ValidateModel
  {
    return new Validator();
  }
}
