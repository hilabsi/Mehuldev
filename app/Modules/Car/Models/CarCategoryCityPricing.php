<?php

namespace App\Modules\Car\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Car\Validators\CarCategoryCityPricing as Validator;

class CarCategoryCityPricing extends Model implements HasValidations
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_car_category_city_pricing';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'country_id',
    'from_city',
    'to_city',
    'start_price',
    'km_price',
    'minimum_price',
    'minute_price',
    'range_percent',
    'factor',
    'category_id',
    'free_wait_time',
    'wait_minute_price',
  ];

  protected $with = ['category'];

  public function category()
  {
    return $this->belongsTo(CarCategory::class, 'category_id');
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
