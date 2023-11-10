<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBusinessProfile extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_business_profiles';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'company_name',
    'company_address',
    'email',
    'uid',
  ];

  /**
   * Owner.
   *
   * @return BelongsTo
   */
  public function user ()
  {
    return $this -> belongsTo(User::class, 'user_id');
  }
}
