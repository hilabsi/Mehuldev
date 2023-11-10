<?php

namespace App\Modules\Support\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Support\Validators\SupportQuestion as Validator;

class SupportCategoryQuestion extends Model implements HasValidations
{
  use ModelDefaults;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_support_questions';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'name',
    'type',
    'category_id',
    'language_id',
    'enable_help',
    'text',
    'action',
    'link',
  ];

  public function category()
  {
    return $this->belongsTo(SupportCategory::class, 'category_id');
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
