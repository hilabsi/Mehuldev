<?php

namespace App\Modules\Support\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Support\Validators\SupportCategory as Validator;

class SupportCategory extends Model implements HasValidations
{
  use ModelDefaults;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_support_categories';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'type',
    'name',
    'language_id',
  ];

  public function questions()
  {
    return $this->hasMany(SupportCategoryQuestion::class, 'category_id');
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
