<?php

namespace App\Modules\Car\Models;

use App\Support\Traits\UsesUUID;
use App\Modules\Trip\Models\Trip;
use App\Services\FireStoreService;
use App\Modules\Driver\Models\Driver;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Car\Validators\CarCategory as Validator;

class CarSession extends Model implements HasValidations
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
  protected $table = 'd_car_sessions';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'car_id', 'driver_id', 'trips', 'hours', 'total_cost', 'status',
  ];

  public function car()
  {
    return $this->belongsTo(Car::class, 'car_id');
  }

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }

  public function trips()
  {
    return $this->hasMany(Trip::class, 'session_id');
  }

  public function refreshFirestore()
  {
    $this->refresh();
    FireStoreService::client()->collection('drivers')
      ->document($this->driver->firestore_ref)->update([
                                                         ['path' => 'current_car.hours', 'value' => floor($this->hours)],
                                                         ['path' => 'current_car.trips', 'value' => $this->trips],
                                                         ['path' => 'current_car.total_cost', 'value' => number_format($this->total_cost, 0, ',', '.')],
                                                       ]);
  }

  /**
   * Gets model's operations' validation rules.
   *
   * @return ValidateModel
   */
  public static function validations (): ValidateModel
  {
    return new Validator();
  }
}
