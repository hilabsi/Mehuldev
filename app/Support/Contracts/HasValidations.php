<?php

namespace App\Support\Contracts;

interface HasValidations
{
    /**
     * Gets model's operations' validation roles.
     *
     * @return ValidateModel
     */
    public static function validations(): ValidateModel;
}
