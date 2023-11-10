<?php

namespace App\Modules\User\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVisit extends Model
{
  use SpatialTrait;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_visits';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'address',
    'location',
  ];

  protected $spatialFields = [
    'location'
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
