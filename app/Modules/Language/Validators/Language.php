<?php

namespace App\Modules\Language\Validators;

use App\Support\Contracts\ValidateModel;

class Language implements ValidateModel
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

        ];
    }
}
