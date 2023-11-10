<?php

namespace App\Modules\Invoice\Models;

use App\Support\Traits\UsesUUID;
use App\Support\Traits\CheckStatus;
use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use App\Support\Traits\UsesPasswords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Invoice\Validators\Invoice as Validator;

class ClientInvoice extends Model implements HasValidations
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
  protected $table = 'd_client_invoices';

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
    'trip_id',
    'invoice_date',
    'due_date',
    'code',
    'total',
    'terms',
    'notes',
    'download_url'
  ];

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
