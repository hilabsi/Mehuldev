<?php

namespace App\Modules\Car\Models;

use Illuminate\Database\Eloquent\Model;

class CarDocument extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_car_documents';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'car_id',
    'document_id',
    'file',
  ];

  public function car()
  {
    return $this->belongsTo(Car::class, 'car_id');
  }
}
