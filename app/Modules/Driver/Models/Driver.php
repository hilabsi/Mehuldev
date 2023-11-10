<?php

namespace App\Modules\Driver\Models;

use App\Modules\Car\Models\Car;
use App\Modules\Car\Models\CarSession;
use App\Modules\Trip\Models\Trip;
use App\Modules\User\Models\UserPasswordReset;
use App\Services\FireStoreService;
use App\Support\Traits\ModelDefaults;
use App\Support\Traits\UsesUUID;
use App\Modules\Country\Models\City;
use App\Modules\Country\Models\Country;
use App\Modules\Partner\Models\Partner;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Settings\Models\LicenseType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Driver\Validators\Driver as Validator;
use Illuminate\Support\Facades\Hash;
use Kreait\Firebase\DynamicLink\AndroidInfo;
use Kreait\Firebase\DynamicLink\CreateDynamicLink;
use Kreait\Firebase\DynamicLink\IOSInfo;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Driver extends Model implements HasValidations, AuthenticatableContract, AuthorizableContract, JWTSubject
{
  use Authenticatable;
  use Authorizable;
  use UsesUUID;
  use ModelDefaults;
  use Notifiable;
  use SpatialTrait;

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
  protected $table = 'd_drivers';


  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'string'
  ];

  protected $appends = [
    'name',
  ];

  /**
   * The attributes that are spatial fields.
   *
   * @var array
   */
  protected array $spatialFields = [
    'location'
  ];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'first_name',
    'last_name',
    'phone',
    'partner_id',
    'license_type_id',
    'password',
    'email',
    'country_id',
    'city_id',
    'birthday',
    'gender',
    'id_type',
    'license_number',
    'id_number',
    'is_verified',
    'device_id',
    'is_online',
    'picture',
    'firestore_ref',
    'location',
    'status',
    'car_id',
    'current_session',
    'current_trip',
    'heading',
    'last_online',
  ];

  /**
   * Auto-generate full name attribute
   *
   * @return string
   */
  public function getNameAttribute()
  {
    return $this->first_name . ' ' . $this->last_name;
  }

  /**
   * Check if is reachable by FCM.
   *
   * @return bool
   */
  public function hasDeviceId()
  {
    return !!$this->device_id;
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
      if ($document) {
        $d = uploader($document, 'drivers', $this -> attributes['id']);

        $this->documents()->create(['file' => $d, 'document_id' => $key]);
      }
    }
  }

  public function documents()
  {
    return $this->hasMany(DriverDocument::class, 'driver_id');
  }
  /**
   * @return BelongsTo
   */
  public function licenseType()
  {
    return $this->belongsTo(LicenseType::class, 'license_type_id');
  }

  /**
   * @return BelongsTo
   */
  public function partner() {
    return $this->belongsTo(Partner::class, 'partner_id');
  }

  /**
   * @return BelongsTo
   */
  public function city()
  {
    return $this->belongsTo(City::class, 'city_id');
  }

  /**
   * @return BelongsTo
   */
  public function country()
  {
    return $this->belongsTo(Country::class, 'country_id');
  }

  public function routeNotificationForTwilio ()
  {
    return $this -> getFullPhoneNumber();
  }

  public function car()
  {
    return $this->belongsTo(Car::class, 'car_id');
  }

  public function hasCar()
  {
    return !!$this->car_id;
  }


  public function getFullPhoneNumber()
  {
    return "{$this->country->phone_prefix}{$this->phone}";
  }

  /**
   * Auto-upload user picture.
   *
   * @param $file
   */
  public function setPictureAttribute ($file)
  {
    if ($file)
      $this -> attributes['picture'] = uploader($file, 'drivers', $this -> attributes['id']);
  }

  /**
   * Auto-format picture url.
   *
   * @return string|null
   */
  public function getPictureAttribute ()
  {
    return isset($this -> attributes['picture']) ? s3($this -> attributes['picture']) : null;
  }

  /**
   * Auto-hashing new password.
   *
   * @param null $value
   */
  public function setPasswordAttribute ($value = null)
  {
    if ($value) {
      $this -> attributes['password'] = Hash ::make($value);
    }
  }

  /**
   * Reset password requests.
   *
   * @return HasMany
   */
  public function passwordResets()
  {
    return $this->hasMany(DriverPasswordReset::class, 'driver_id');
  }

  /**
   * Specifies the user's FCM token
   *
   * @return string
   */
  public function routeNotificationForFcm()
  {
    return $this->device_id;
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

  public function createInvitationLink()
  {
    $dyn = app('firebase.dynamic_links');

    $rand = rand(111111, 999999);
    $this->update(['invite_code' => $rand]);

    $action = CreateDynamicLink::forUrl('https://lobi.at/i/'.$rand)
      ->withDynamicLinkDomain('https://lobiat.page.link')
      ->withShortSuffix()
      ->withIOSInfo(
        IOSInfo::new()
          ->withAppStoreId('1575220219')
          ->withBundleId('com.cresignzone.lobi')
          ->withCustomScheme('lobi://')
          ->withFallbackLink('https://lobi.at')
      )
      ->withAndroidInfo(
        AndroidInfo::new()
          ->withFallbackLink('https://lobi.at')
          ->withPackageName('com.cresignzone.lobi')
      );

    $link = $dyn->createDynamicLink($action);

    $this->updateFirestore([
                             ['path' => 'invite_code', 'value' => $rand],
                             ['path' => 'invite_link', 'value' => (string)$link]
                           ]);
  }

  public function avgRating()
  {
    return round(DriverRating::whereDriverId($this->id)->avg('rating'), 1);
  }

  /**
   * @param $array
   */
  public function updateFirestore($array)
  {
    FireStoreService::client()->collection('drivers')
      ->document($this->firestore_ref)->update($array);
  }

  public function currentTrip()
  {
    return $this->belongsTo(Trip::class, 'current_trip');
  }

  public function currentSession()
  {
    return $this->belongsTo(CarSession::class, 'current_session');
  }

  /**
   * Creates document for user in firestore
   *
   * @return mixed
   */
  public function configureFirestore()
  {
    $ref = FireStoreService::client()->collection('drivers')->add([
                                                                    'driver_id'         => $this->id,
                                                                    'current_trip'      => null,
                                                                    'rating'            => 0,
                                                                    'rate_trip'         => null,
                                                                    'request'           => null,
                                                                    'invite_link'       => '',
                                                                    'invite_code'       => '',
                                                                    'current_location'  => ['U' => 1, 'k' => 1, 'heading' => 1],
                                                                    'email'             => $this->email,
                                                                    'first_name'        => $this->first_name,
                                                                    'last_name'         => $this->last_name,
                                                                    'language_id'       => $this->language_id,
                                                                    'phone'             => $this->phone,
                                                                    'full_phone'        => $this->getFullPhoneNumber(),
                                                                    'country'           => [
                                                                      'id'      => $this->country->id,
                                                                      'alpha2'  => $this->country->alpha2,
                                                                      'alpha3'  => $this->country->alpha3,
                                                                      'prefix'  => $this->country->phone_prefix,
                                                                    ],
                                                                    'picture'           => $this->getDocument('profile'),
                                                                  ]);

    $this->update(['firestore_ref' => $ref->id()]);

    $this->refresh();

    $this->createInvitationLink();

    return $ref->id();
  }

  public function refreshFirestore()
  {
    $this->updateFirestore([
                             ['path' => 'email',              'value' => $this->email],
                             ['path' => 'first_name',         'value' => $this->first_name],
                             ['path' => 'last_name',          'value' => $this->last_name],
                             ['path' => 'language_id',        'value' => $this->language_id],
                             ['path' => 'phone',              'value' => $this->phone],
                             ['path' => 'full_phone',         'value' => $this->getFullPhoneNumber()],
                             ['path' => 'country',            'value' => [
                               'id'      => $this->country->id,
                               'alpha2'  => $this->country->alpha2,
                               'alpha3'  => $this->country->alpha3,
                               'prefix'  => $this->country->phone_prefix,
                             ]],
                             ['path' => 'picture',  'value' => $this->getDocument('profile')],
                           ]);
  }
}
