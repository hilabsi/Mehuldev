<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Country\Models\Country;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserView extends Model
{
  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'id' => 'string'
  ];

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'u_v_users';

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

  /**
   * Auto-format picture url.
   *
   * @return string|null
   */
  public function getPictureAttribute ()
  {
    return $this -> attributes['photo'] ? s3($this -> attributes['photo']) : null;
  }

}
