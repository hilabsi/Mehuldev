<?php

namespace App\Modules\Car\Models;

use Illuminate\Database\Eloquent\Model;

class CarView extends Model
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
    protected $table = 'v_cars';
}
