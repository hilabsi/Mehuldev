<?php

namespace App\Modules\Trip\Models;

use App\Modules\User\Models\User;
use App\Services\FireStoreService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripTaxiPayment extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_trip_taxi_payments';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'trip_id',
    'type',
    'payment_intent_id',
    'user_id',
    'status',
    'reason',
    'client_secret',
    'firestore_ref',
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

  /**
   * Associated Trip.
   *
   * @return BelongsTo
   */
  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  /**
   * @param $array
   */
  public function updateFirestore($array): void
  {
    FireStoreService::client()->collection('payments')
      ->document($this->firestore_ref)->update($array);
  }
}
