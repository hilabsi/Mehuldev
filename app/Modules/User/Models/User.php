<?php

namespace App\Modules\User\Models;

use App\Modules\Car\Models\CarCategory;
use App\Modules\Language\Models\Language;
use App\Modules\Trip\Models\Trip;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Kreait\Firebase\DynamicLink\AndroidInfo;
use Kreait\Firebase\DynamicLink\CreateDynamicLink;
use Kreait\Firebase\DynamicLink\IOSInfo;
use Laravel\Cashier\Billable;
use App\Support\Traits\UsesUUID;
use App\Services\FireStoreService;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Auth\Authenticatable;
use App\Support\Traits\ModelDefaults;
use App\Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Laravolt\Avatar\Facade;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\User\Validators\User as Validator;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class User extends Model implements HasValidations, AuthenticatableContract, AuthorizableContract, JWTSubject
{
  use Billable;
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
  protected $table = 'd_users';

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'string'
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
   * @param  array|null  $value
   */
  public function setLocationAttribute(?array $value)
  {
    if ($value && count($value) === 2) {

      $this->attributes['location'] =  new Point($value[0], $value[1], 4326);

      $this->updateFirestore([
                               ['path' => 'current_location', 'value' => ['U' => $value[0], 'k' => $value[1]]]
                             ]);
    }
  }

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'language_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'country_id',
    'is_phone_verified',
    'is_active',
    'firestore_ref',
    'phone_verification_attempts',
    'password',
    'location',
    'picture',
    'device_id',
    'ref',
    'invite_code',
    'referral_code',
    'current_trip',
  ];

  public function trips()
  {
    return $this->hasMany(Trip::class, 'user_id');
  }

  public function currentTrip()
  {
    return $this->belongsTo(Trip::class, 'current_trip');
  }

  /**
   * Auto-generate full_name.
   *
   * @return string
   */
  public function getFullNameAttribute()
  {
    return "{$this->first_name} {$this->last_name}";
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
   * User Social accounts.
   *
   * @return HasMany
   */
  public function social ()
  {
    return $this -> hasMany(UserSocialAccount::class, 'user_id');
  }

  public function hasDeviceId()
  {
    return !!$this->device_id;
  }

  /**
   * Associated payment cards.
   *
   * @return HasMany
   */
  public function cards()
  {
    return $this->hasMany(UserCard::class, 'user_id');
  }

  /**
   * @return HasOne
   */
  public function businessWallet()
  {
    return $this->hasOne(UserWallet::class, 'user_id')->where('type', '=', 'business');
  }

  /**
   * @return HasOne
   */
  public function regularWallet()
  {
    return $this->hasOne(UserWallet::class, 'user_id')->where('type', '=', 'regular');
  }

  public function lastVisits()
  {
    return $this->hasMany(UserVisit::class, 'user_id');
  }

  /**
   * Reset password requests.
   *
   * @return HasMany
   */
  public function passwordResets()
  {
    return $this->hasMany(UserPasswordReset::class, 'user_id');
  }

  public function phoneVerificationAttempts()
  {
    return $this->hasMany(UserPhoneVerificationAttempt::class, 'user_id');
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
      $this -> attributes['picture'] = uploader($file, 'users', $this -> attributes['id']);
  }

  /**
   * Auto-format picture url.
   *
   * @return string|null
   */
  public function getPictureAttribute ()
  {
    return $this -> attributes['picture'] ? s3($this -> attributes['picture']) : null;
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

  public function routeNotificationForTwilio ()
  {
    return $this -> getFullPhoneNumber();
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
   * Flag user phone as verified.
   */
  public function makeVerified()
  {
    $this->update([
                    'is_phone_verified' => true
                  ]);
  }

  public function configureUserAccount()
  {
    $this->attributes['picture'] = Facade::create($this->email)->toGravatar();

    $this->save();

    $this->places()->create(['type' => 'home',]);

    $this->places()->create(['type' => 'work',]);

    $this->business()->create();

    $this->configureWallets();
  }

  public function coupons()
  {
    return $this->hasMany(UserCoupon::class, 'user_id');
  }

  public function wallets()
  {
    return $this->hasMany(UserWallet::class, 'user_id');
  }

  public function configureWallets()
  {
    $this->wallets()->create(['type' => 'regular']);
    $this->wallets()->create(['type' => 'business']);
  }

  public function avgRating()
  {
    return round(UserRating::whereUserId($this->id)->avg('rating'), 1);
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

  public function language()
  {
    return $this->belongsTo(Language::class, 'language_id');
  }

  /**
   * Creates document for user in firestore
   *
   * @return mixed
   */
  public function configureFirestore()
  {
    $distance = 0;
    $time = 0;
    $wallet = 'regular';
    $user = $this;

    $categories = CarCategory::enabled()->get()->map(function ($category) use ($distance, $time, $wallet, $user) {
      return calcCategoryPrice($category, $distance, $time, $wallet, $user);
    });

    $ref = FireStoreService::client()->collection('users')->add([
                                                                  'user_id'           => $this->id,
                                                                  'business_profile'  => [
                                                                    'company_address' => null,
                                                                    'uid'             => null,
                                                                    'company_name'    => null,
                                                                    'email'           => null
                                                                  ],
                                                                  'wallet_type'       => 'regular',
                                                                  'trip_pricing'      => $categories->toArray(),
                                                                  'current_trip'      => null,
                                                                  'has_password'      => !!$this->password,
                                                                  'regular_wallet'    => [
                                                                    'cards' => $this->regularWallet->cards->map(function ($item) {
                                                                      return [
                                                                        'number' => $item->protected_number,
                                                                        'id'     => $item->id,
                                                                        'image'  => $item->image
                                                                      ];
                                                                    })->toArray(),
                                                                    'active_card_id'  => null,
                                                                    'default'         => $this->regularWallet->default_payment_type,
                                                                    'coupons'         => [],
                                                                    'active_coupon_id'=> null,
                                                                  ],
                                                                  'business_wallet'    => [
                                                                    'cards' => $this->businessWallet->cards->map(function ($item) {
                                                                      return [
                                                                        'number' => $item->protected_number,
                                                                        'id'     => $item->id,
                                                                        'image'  => $item->image
                                                                      ];
                                                                    })->toArray(),
                                                                    'active_card_id'  => null,
                                                                    'default'         => $this->businessWallet->default_payment_type,
                                                                    'coupons'         => [],
                                                                    'active_coupon_id'=> null,
                                                                  ],
                                                                  'current_location'  => ['U' => 1, 'k' => 1],
                                                                  'email'             => $this->email,
                                                                  'first_name'        => $this->first_name,
                                                                  'last_name'         => $this->last_name,
                                                                  'has_business'      => false,
                                                                  'language_id'       => $this->language_id,
                                                                  'last_visited'      => [],
                                                                  'phone'             => $this->phone,
                                                                  'full_phone'        => $this->getFullPhoneNumber(),
                                                                  'country'           => [
                                                                    'id'      => $this->country->id,
                                                                    'alpha2'  => $this->country->alpha2,
                                                                    'alpha3'  => $this->country->alpha3,
                                                                    'prefix'  => $this->country->phone_prefix,
                                                                  ],
                                                                  'picture'           => $this->attributes['picture'],
                                                                  'places'            => $this->places->map(function ($place) {
                                                                    return [
                                                                      'id'        => $place->id,
                                                                      'address'   => $place->address,
                                                                      'type'      => $place->type,
                                                                      'location'  => $place->location,
                                                                    ];
                                                                  })->toArray(),
                                                                ]);

    $this->update(['firestore_ref' => $ref->id()]);

    $this->refresh();

    $this->createInvitationLink();

    return $ref->id();
  }

  /**
   * @param $array
   */
  public function updateFirestore($array)
  {
    FireStoreService::client()->collection('users')
      ->document($this->firestore_ref)->update($array);
  }

  /**
   * Return user' places
   *
   * @return HasMany
   */
  public function places()
  {
    return $this->hasMany(UserPlace::class, 'user_id');
  }

  /**
   * Update user's business profile data.
   *
   * @param  array  $data
   */
  public function updateBusiness(array $data)
  {
    if(! $this->business) {
      $this->business()->create($data);
    } else $this->business->update($data);
  }

  public function business()
  {
    return $this->hasOne(UserBusinessProfile::class, 'user_id');
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
