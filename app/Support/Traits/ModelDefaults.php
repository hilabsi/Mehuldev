<?php

namespace App\Support\Traits;

trait ModelDefaults
{
    /**
     * Overwriting saved attribute to lowercase.
     *
     * @param String $value
     */
    public function setStatusAttribute(String $value) : void
    {
        $this->attributes['status'] = strtolower($value);
    }

    /**
     * Scoping to only enabled rows.
     *
     * @param $query
     * @return mixed
     */
    public function scopeEnabled($query)
    {
        return $query->whereStatus('enabled');
    }
}
