<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Settings\Validators\CarBrand as Validator;

class CarBrand extends Model implements HasValidations
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 's_car_brands';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'title', 'code',
  ];

  public function getTitleAttribute()
  {
    return trim($this->attributes['title']);
  }

  public function models()
  {
    return $this->hasMany(CarModel::class, 'brand_id');
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
