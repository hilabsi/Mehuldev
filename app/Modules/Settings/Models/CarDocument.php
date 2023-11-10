<?php

namespace App\Modules\Settings\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Settings\Validators\PartnerDocument as Validator;

class CarDocument extends Model implements HasValidations
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 's_car_documents';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'field_name', 'is_required',
  ];

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
