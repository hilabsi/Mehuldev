<?php

namespace App\Modules\Country\Models;

use App\Modules\PaymentMethod\Models\PaymentMethod;
use App\Modules\PayoutMethod\Models\PayoutMethod;
use App\Support\Traits\ModelDefaults;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryView extends Model
{
    use ModelDefaults;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'v_countries';

    /**
     * Related currency
     *
     * @return BelongsTo
     */
    public function currency ()
    {
        return $this -> belongsTo(City::class, 'currency_id');
    }

    public function paymentMethods ()
    {
        return $this -> belongsToMany(PaymentMethod::class,'s_country_payments', 'country_id', 'payment_id');
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
}
