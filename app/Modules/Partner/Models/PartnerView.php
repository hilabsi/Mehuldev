<?php

namespace App\Modules\Partner\Models;

use App\Support\Traits\CheckStatus;
use Illuminate\Database\Eloquent\Model;

class PartnerView extends Model
{
  use CheckStatus;

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
  protected $table = 'v_partners';
}
