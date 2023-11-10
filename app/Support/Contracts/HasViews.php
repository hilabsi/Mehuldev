<?php

namespace App\Support\Contracts;

use App\Support\ViewsPresenters\ViewsPresenter;

interface HasViews
{
    /**
     * Gets model's related views.
     *
     * @param array $params
     * @return ViewsPresenter
     */
    public static function views(array $params = []): ViewsPresenter;
}
