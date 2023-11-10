<?php

namespace App\Modules\Country\Validators;

use App\Support\Contracts\ValidateModel;

class City implements ValidateModel
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
            'name'        => 'sometimes|required|max:191',
            'lat'         => 'sometimes|required|numeric',
            'status'      => 'sometimes|required|in:enabled,disabled',
            'lng'         => 'sometimes|required|numeric',
            'country_id'  => 'sometimes|required|numeric|enabled:s_countries',
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
          'name'        => 'required|max:191',
          'lat'         => 'required|numeric',
          'status'      => 'required|in:enabled,disabled',
          'lng'         => 'required|numeric',
          'country_id'  => 'required|numeric|enabled:s_countries',
        ];
    }
}
