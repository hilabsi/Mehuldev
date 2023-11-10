<?php

namespace App\Modules\Trip\Models;

use App\Modules\User\Models\User;
use App\Modules\Driver\Models\Driver;
use App\Support\Traits\UsesUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripChatMessage extends Model
{
  use UsesUUID;

  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_trip_chat_messages';

  public $incrementing = false;

  protected $casts = [
    'id' => 'string',
  ];

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'trip_id',
    'user_id',
    'driver_id',
    'message',
    'issuer',
  ];

  /**
   * Associated Trip.
   *
   * @return BelongsTo
   */
  public function trip()
  {
    return $this->belongsTo(Trip::class, 'trip_id');
  }

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
}
