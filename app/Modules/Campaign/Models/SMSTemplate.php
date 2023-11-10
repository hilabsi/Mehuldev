<?php

namespace App\Modules\Campaign\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Campaign\Validators\SMSTemplate as Validator;

class SMSTemplate extends Model implements HasValidations
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_campaign_sms_templates';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'title',
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
