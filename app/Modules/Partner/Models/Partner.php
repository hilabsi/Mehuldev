<?php

namespace App\Modules\Partner\Models;

use App\Modules\Car\Models\Car;
use App\Services\FireStoreService;
use App\Support\Traits\UsesUUID;
use App\Support\Traits\CheckStatus;
use App\Modules\Country\Models\City;
use App\Support\Traits\UsesPasswords;
use App\Modules\Driver\Models\Driver;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Country\Models\Country;
use Illuminate\Notifications\Notifiable;
use App\Support\Contracts\ValidateModel;
use App\Modules\Language\Models\Language;
use App\Support\Contracts\HasValidations;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Partner\Validators\Partner as Validator;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Partner extends Model implements HasValidations, AuthenticatableContract, AuthorizableContract, JWTSubject
{
  use UsesUUID;
  use Notifiable;
  use CheckStatus;
  use UsesPasswords;
  use Authenticatable;
  use Authorizable;

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
  protected $table = 'd_partners';

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'id'        => 'string',
    'is_deleted'=> 'integer'
  ];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'company_name',
    'first_name',
    'percent',
    'last_name',
    'country_id',
    'city_id',
    'password',
    'email',
    'phone',
    'language_id',
    'billing_type',
    'address',
    'fna',
    'uid',
    'account_owner',
    'iban',
    'bic',
    'is_active',
    'is_verified',
    'is_deleted',
    'status',
    'verification_code',
    'is_email_verified',
    'firestore_ref'
  ];

  /**
   * @return HasMany
   */
  public function drivers()
  {
    return $this->hasMany(Driver::class, 'partner_id');
  }

  public function country()
  {
    return $this->belongsTo(Country::class, 'country_id');
  }

  public function city()
  {
    return $this->belongsTo(City::class, 'city_id');
  }

  /**
   * @return HasMany
   */
  public function cars()
  {
    return $this->hasMany(Car::class, 'partner_id');
  }

  public function getCompanyNameAttribute()
  {
    return $this->attributes['company_name'] ?? $this->attributes['first_name'] . ' ' . $this->attributes['lat_name'];
  }

  public function language()
  {
    return $this->belongsTo(Language::class, 'language_id');
  }

  /**
   * @param $array
   */
  public function updateFirestore($array)
  {
    FireStoreService::client()->collection('partners')
      ->document($this->firestore_ref)->update($array);
  }

  public function getDocument($key)
  {
    $document = $this->documents()->orderBy('created_at', 'desc')->where('document_id', $key)->first();

    if($document)
      return s3($document->file);
    return null;
  }

  public function setDocuments(array $documents)
  {
    foreach ($documents as $key => $document) {
      if (!is_array($document) && $document) {
        $d = uploader($document, 'partners', $this -> attributes['id']);

        $this->documents()->create(['file' => $d, 'document_id' => $key]);
      }
    }
  }

  public function documents()
  {
    return $this->hasMany(PartnerDocument::class, 'partner_id');
  }

  public function configureFirestore()
  {
    $ref = FireStoreService::client()->collection('partners')->add([
                                                                     'id'   => $this->id,
                                                                     'name' => $this->company_name,
                                                                     'Name' => $this->company_name,
                                                                     'cars' => $this->cars()->where('is_verified', 1)->get()->map(function ($item) {
                                                                       return [
                                                                         'brand'  => $item->brand->title,
                                                                         'name'   => $item->type,
                                                                         'color'  => $item->color,
                                                                         'id'     => $item->id,
                                                                         'lpn'    => $item->lpn,
                                                                         'model'  => $item->model->title,
                                                                         'status' => !!!$item->driver_id ? 'active' : 'busy',
                                                                         'year'   => $item->year,
                                                                       ];
                                                                     })->toArray()
                                                                   ]);

    $this->update(['firestore_ref' => $ref->id()]);

    $this->refresh();

    return $ref->id();
  }

  public function refreshCars()
  {
    $this->updateFirestore([
                             ['path' => 'cars', 'value' => $this->cars()->where('is_verified', 1)->get()->map(function ($item) {
                               return [
                                 'brand'  => $item->brand->title,
                                 'color'  => $item->color,
                                 'name'   => $item->type,
                                 'id'     => $item->id,
                                 'lpn'    => $item->lpn,
                                 'model'  => getCarModel($item),
                                 'status' => !!!$item->driver_id ? 'active' : 'busy',
                                 'year'   => $item->year,
                               ];
                             })->toArray()]
                           ]);
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
