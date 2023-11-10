<?php

namespace App\Modules\Settings\Validators;

use App\Support\Contracts\ValidateModel;

class Settings implements ValidateModel
{

    /**
     * Validate model on edit operation.
     *
     * @param
     *            $id
     *
     * @return array
     */
    public function edit ($id): array
    {
        return [
            'key'   => 'sometimes|required|wordNumberSpacePointUnderscore|max:191|unique:s_settings,key,' . $id,
            'value' => 'sometimes|required|max:32000'
        ];
    }

    /**
     * Validate model on create operation.
     *
     * @return array
     */
    public function create (): array
    {
        return [
            'key'   => 'required|wordNumberSpacePointUnderscore|max:191|unique:s_settings,key',
            'value' => 'required|max:32000'
        ];
    }
}
