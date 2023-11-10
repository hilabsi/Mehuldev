<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;

class DriverDocument extends Model
{
  /**
   * The table associated with the model.
   *
   * @var string
   */
  protected $table = 'd_driver_documents';

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
    'driver_id',
    'document_id',
    'file',
  ];

  public function driver()
  {
    return $this->belongsTo(Driver::class, 'driver_id');
  }
}
