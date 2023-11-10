<?php

namespace App\Modules\CustomPage\Models;

use App\Modules\Language\Models\Language;
use App\Support\Traits\UsesUUID;
use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\CustomPage\Validators\CustomPage as Validator;

class CustomPage extends Model implements HasValidations
{
  use UsesUUID;
  use ModelDefaults;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_custom_screens';

  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = false;

  /**
   * The attributes that should be cast.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'string'
  ];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'id',
    'language_id',
    'content',
  ];

  public function language()
  {
    return $this->belongsTo(Language::class, 'language_id');
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
