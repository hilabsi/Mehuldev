<?php

namespace App\Modules\Car\Validators;

use App\Support\Contracts\ValidateModel;

class CarCategory implements ValidateModel
{
    /**
     * Validate model on edit operation.
     *
     * @param $id
     *
     * @return array
     */
    public function edit ($id): array
    {
        return [
          'name' => 'required|max:191'
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
          'name' => 'required|max:191'
        ];
    }
}
