<?php

namespace App\Support\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface HasApiEndpoints
{
    /**
     * Gets model's related api endpoints representations.
     *
     * @param  Collection|null  $collection
     * @return ApisPresenter
     */
    public static function api(Collection $collection = null): ApisPresenter;
}
