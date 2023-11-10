<?php

namespace App\Modules\Invoice\Models;

use App\Support\Traits\UsesUUID;
use App\Support\Traits\CheckStatus;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use App\Support\Traits\UsesPasswords;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Invoice\Validators\Invoice as Validator;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Invoice extends Model implements HasValidations, AuthenticatableContract, AuthorizableContract, JWTSubject
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
  protected $table = 'd_invoices';

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
    'partner_id',
    'invoice_date',
    'due_date',
    'code',
    'cash',
    'apple',
    'google',
    'card',
    'commission_percent',
    'commission_amount',
    'total',
    'terms',
    'notes',
    'status',
    'download_url'
  ];

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
