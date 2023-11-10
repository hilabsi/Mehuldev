<?php

namespace App\Modules\Language\Models;

use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;

class LanguageView extends Model
{
    use ModelDefaults;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_s_languages';
}
