<?php

namespace App\Modules\Car\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Car\Validators\CarCategory as Validator;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CarCategory extends Model implements HasValidations
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_car_categories';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name',
    'seats',
    'start_price',
    'km_price',
    'minimum_price',
    'minute_price',
    'range_percent',
    'image',
    'factor',
    'free_wait_time',
    'wait_minute_price',
    'status',
    'type',
  ];

  /**
   * @return BelongsToMany
   */
  public function cars() {
    return $this->belongsToMany(Car::class, 'r_car_category', 'category_id', 'car_id');
  }

  public function scopeEnabled($query)
  {
    return $query->whereStatus('enabled');
  }

  /**
   * Auto-upload user picture.
   *
   * @param $file
   */
  public function setImageAttribute ($file)
  {
    if ($file)
      $this -> attributes['image'] = uploader($file, 'car-categories', $this -> attributes['id']);
  }

  /**
   * Auto-format picture url.
   *
   * @return string|null
   */
  public function getImageAttribute ()
  {
    return $this -> attributes['image'] ? s3($this -> attributes['image']) : null;
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
