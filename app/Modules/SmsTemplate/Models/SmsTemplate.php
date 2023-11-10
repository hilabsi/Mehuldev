<?php

namespace App\Modules\SmsTemplate\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\SmsTemplate\Validators\SmsTemplate as Validator;

class SmsTemplate extends Model implements HasValidations
{
  use ModelDefaults;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 's_sms_templates';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'template',
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
