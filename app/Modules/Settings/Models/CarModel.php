<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Settings\Validators\CarModel as Validator;

class CarModel extends Model implements HasValidations
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 's_car_models';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'brand_id',
    'title',
  ];

  public function getTitleAttribute()
  {
    return trim($this->attributes['title']);
  }

  public function brand()
  {
    return $this->belongsTo(CarBrand::class, 'brand_id');
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
