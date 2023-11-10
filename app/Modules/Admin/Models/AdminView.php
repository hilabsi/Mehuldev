<?php

namespace App\Modules\Admin\Models;

use App\Support\Traits\CheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminView extends Model
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
  protected $table = 'v_admins';

  /**
   * Relation to Role Model.
   *
   * @return BelongsTo
   */
  public function role ()
  {
    return $this -> belongsTo(AdminRole::class, 'role_id');
  }
}
