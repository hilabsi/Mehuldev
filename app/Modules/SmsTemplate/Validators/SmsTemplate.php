<?php

namespace App\Modules\SmsTemplate\Validators;

use App\Support\Contracts\ValidateModel;

class SmsTemplate implements ValidateModel
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
          'template' => 'required'
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
