<?php

namespace App\Modules\Language\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Language\Validators\Language as Validator;

class Language extends Model implements HasValidations
{
    use ModelDefaults;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 's_languages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'shortcut',
        'status'
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
