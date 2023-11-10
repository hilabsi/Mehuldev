<?php

namespace App\Support\Exceptions;

use Exception;

class InvalidEnumerationException extends Exception
{
    protected $code = 409;
}
