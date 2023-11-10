<?php

namespace App\Support\Contracts;

interface HasUrls
{
    /**
     * Gets all related models actions routes.
     *
     * @return UrlPresenter
     */
    public function getUrlAttribute(): UrlPresenter;
}
