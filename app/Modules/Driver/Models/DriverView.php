<?php

namespace App\Modules\Driver\Models;

use Illuminate\Database\Eloquent\Model;

class DriverView extends Model
{
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
    protected $table = 'v_u_drivers';
}
