<?php

namespace App\Modules\Admin\Models;

use App\Support\Traits\CheckStatus;
use App\Support\Traits\UsesPasswords;
use Illuminate\Auth\Authenticatable;
use App\Support\Traits\UsesUUID;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Admin\Validators\Admin as Validator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Admin extends Model implements HasValidations, AuthenticatableContract, AuthorizableContract, JWTSubject
{
  use UsesUUID;
  use Notifiable;
  use CheckStatus;
  use Authenticatable;
  use Authorizable;
  use UsesPasswords;

  /**
   * Indicates if the IDs are auto-incrementing.
   *
   * @var bool
   */
  public $incrementing = false;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_admins';

  /**
   * The attributes that should be cast to native types.
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
    'name',
    'email',
    'role_id',
    'country_id',
    'is_active',
    'is_deleted',
    'password',
  ];

  /**
   * Relation to Role Model.
   *
   * @return BelongsTo
   */
  public function role ()
  {
    return $this -> belongsTo(AdminRole::class, 'role_id');
  }
  
  /**
   * Related country.
   *
   * @return BelongsTo
   */
  public function country ()
  {
    return $this -> belongsTo(Country::class, 'country_id');
  }

  /**
   * Get the identifier that will be stored in the subject claim of the JWT.
   *
   * @return mixed
   */
  public function getJWTIdentifier ()
  {
    return $this -> getKey();
  }

  /**
   * Return a key value array, containing any custom claims to be added to the JWT.
   *
   * @return array
   */
  public function getJWTCustomClaims ()
  {
    return [];
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
