<?php

namespace App\Modules\User\Models;

use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlace extends Model
{
  use SpatialTrait;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_user_places';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'user_id',
    'type',
    'location',
    'address',
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
    if ($value && count($value) === 2)
      $this->attributes['location'] =  new Point($value[0], $value[1]); #DB::raw("(ST_GeomFromText('POINT({$value[0]} {$value[1]})'))");
  }

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
