<?php

namespace App\Modules\Country\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Country\Validators\City as Validator;

class City extends Model implements HasValidations
{
  use ModelDefaults;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 's_country_cities';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name',
    'lat',
    'lng',
    'status',
    'country_id'
  ];

  protected $casts = [
    'country_id' => 'integer',
  ];

  /**
   * Related country
   *
   * @return BelongsTo
   */
  public function country ()
  {
    return $this -> belongsTo(Country::class, 'country_id');
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
