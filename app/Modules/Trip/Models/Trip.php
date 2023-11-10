<?php

namespace App\Modules\Trip\Models;

use App\Modules\Car\Models\Car;
use App\Modules\Car\Models\CarCategory;
use App\Modules\Driver\Models\Driver;
use App\Modules\User\Models\User;
use App\Modules\User\Models\UserCard;
use App\Services\FireStoreService;
use App\Support\Traits\UsesUUID;
use Google\Cloud\Core\GeoPoint;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Trip\Validators\Trip as Validator;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model implements HasValidations
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
  protected $table = 'd_trips';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'path',
    'source_address',
    'source_location',
    'pickup_address',
    'pickup_location',
    'destination_address',
    'destination_location',
    'status',
    'driver_id',
    'car_id',
    'firestore_ref',
    'user_id',
    'has_stops',
    'cancel_reason',
    'wallet_type',
    'payment_type',
    'card_id',
    'cost',
    'place_id',
    'payment_intent_id',
    'scheduled_on',
    'type',
    'car_category_id',
    'route_image',
    'sent_iam_here',
    'ended',
    'time',
    'distance',
    'did_run',
    'started_at',
    'wait_time_cost',
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

  public function category()
  {
    return $this->belongsTo(CarCategory::class, 'car_category_id');
  }

  public function car()
  {
    return $this->belongsTo(Car::class, 'car_id');
  }

  public function getEnc()
  {
    return TripRoute::whereTripId($this->id)->first()->enc;
  }

  public function stops()
  {
    return $this->hasMany(TripStop::class, 'trip_id');
  }

  public function log(string $type, Point $user_location, ?Point $driver_location)
  {
    $this->logs()->create(['type' => $type, 'user_location' => $user_location, 'driver_location' => $driver_location]);
  }

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
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

  public function logs()
  {
    return $this->hasMany(TripLog::class, 'trip_id');
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

  /**
   * @return HasMany
   */
  public function chat(): HasMany
  {
    return $this->hasMany(TripChatMessage::class, 'trip_id');
  }

  /**
   * @param $array
   */
  public function updateFirestore($array): void
  {
    FireStoreService::client()->collection('trips')
      ->document($this->firestore_ref)->update($array);
  }

  public function configureFirestore()
  {
    return FireStoreService::client()->collection('trips')->add([
                                                                  'user' => [
                                                                    'name'  => $this->user->full_name,
                                                                    'image' => $this->user->picture,
                                                                  ],
                                                                  'ended' => false,
                                                                  'sent_iam_here' => false,
                                                                  'source'      => [
                                                                    'address' => $this->source_address,
                                                                    'location'=> ['U' => $this->source_location->getLat(), 'k' => $this->source_location->getLng()],
                                                                  ],
                                                                  'destination' => [
                                                                    'address' => $this->destination_address,
                                                                    'location'=> ['U' => $this->destination_location->getLat(), 'k' => $this->destination_location->getLng()],
                                                                  ],
                                                                  'pickup' => [
                                                                    'address' => $this->pickup_address,
                                                                    'location'=> ['U' => $this->pickup_location->getLat(), 'k' => $this->pickup_location->getLng()],
                                                                  ],
                                                                  'last_stop' => 0,
                                                                  'stops' => $this->stops()->orderBy('order', 'asc')->get()->map(function ($stop) {
                                                                    return [
                                                                      'order'       => $stop->order,
                                                                      'address'     => $stop->address,
                                                                      'location'    => ['U' => $stop->location->getLat(), 'k' => $stop->location->getLng()],
                                                                      'reached_at'  => $stop->reached_at,
                                                                    ];
                                                                  })->toArray(),
                                                                  'route_image'       => null,
                                                                  'type'              => $this->type,
                                                                  'scheduled_on'      => $this->scheduled_on,
                                                                  'cost'              => '€'.$this->cost,
                                                                  'payment_type'      => $this->payment_type,
                                                                  'wallet_type'       => $this->wallet_type,
                                                                  'chat'              => [],
                                                                  'driver'            => null,
                                                                  'has_driver'        => false,
                                                                  'driver_arrived_at' => null,
                                                                  'started_at'        => null,
                                                                  'trip_id'           => $this->id,
                                                                  'user_id'           => $this->user_id,
                                                                  'search_failed'     => false,
                                                                  'status'            => 'pending',
                                                                  'car_category_id'   => $this->car_category_id,
                                                                ]
    );
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * Update trip's document hash on firestore
   *
   * @param  string  $hash
   */
  public function setDocumentHash(string $hash)
  {
    $this->update([
                    'firestore_ref' => $hash
                  ]);
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
