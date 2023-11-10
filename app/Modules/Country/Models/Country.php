<?php

namespace App\Modules\Country\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use App\Support\Contracts\ValidateModel;
use App\Support\Contracts\HasValidations;
use App\Modules\Country\Validators\Country as Validator;

class Country extends Model implements HasValidations
{
    use ModelDefaults;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 's_countries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'status',
        'name',
        'alpha2',
        'alpha3',
        'phone_prefix',
        'currency_id',
        'icon'
    ];

    /**
     * Auto-upload icon photo.
     *
     * @param
     *            $file
     */
    public function setIconAttribute ($file)
    {
        if ($file) {
            $this -> attributes['icon'] = uploader($file, 'countries', $this -> attributes['id']);
        }
    }

    /**
     * Auto-format icon url.
     *
     * @return string|null
     */
    public function getIconAttribute ()
    {
        return $this -> attributes['icon'] ? s3($this -> attributes['icon']) : null;
    }

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
