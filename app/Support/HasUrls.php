<?php

namespace App\Support;

use App\Support\Contracts\UrlPresenter;

trait HasUrls
{
    /**
     * Get models resource urls
     *
     * @return UrlPresenter
     */
    public function getUrlAttribute() : UrlPresenter
    {
        $class_name = "App\Modules\\".$this->module."\UrlPresenters\\".(isset($this->urls) ? $this->urls : str_replace('App\\', '', get_class($this)).'Presenter');

        return new $class_name($this);
    }
}
