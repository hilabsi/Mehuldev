<?php

namespace App\Modules\Settings\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Settings\Validators\Settings as Validator;

class Settings extends Model implements HasValidations
{
    use ModelDefaults;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 's_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value'
    ];

    /**
     * Gets model's operations' validation roles.
     *
     * @return ValidateModel
     */
    public static function validations (): ValidateModel
    {
        return new Validator();
    }
}
