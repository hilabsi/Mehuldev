<?php

namespace App\Modules\Driver\Models;

use App\Modules\User\Models\User;
use App\Modules\Trip\Models\Trip;
use App\Support\Traits\UsesUUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverNotification extends Model
{
  use UsesUUID;

  public $incrementing = false;

  protected $casts = [
    'read_at' => 'timestamp',
    'deleted_at' => 'timestamp',
    'id' => 'string',
  ];
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_driver_notifications';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'driver_id',
    'title',
    'description',
    'read_at',
    'deleted_at',
  ];

  /**
   * @return BelongsTo
   */
  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }

  public function isRead()
  {
    return !!$this->read_at;
  }

  public function isDeleted()
  {
    return !!$this->deleted_at;
  }
}
