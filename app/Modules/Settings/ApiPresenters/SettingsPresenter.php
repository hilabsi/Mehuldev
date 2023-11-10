<?php

namespace App\Modules\Settings\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class SettingsPresenter extends ApisPresenter
{

    /**
     * Base representation of collection.
     *
     * @return array
     */
    public function present (): array
    {
        return $this -> collection -> map(function ($item) {
            return $this -> item($item);
        })
            -> toArray();
    }

    public function item ($item)
    {
        return [
            'id' => $item -> id,
            'key' => $item -> key,
            'value' => $item -> value
        ];
    }
}
