<?php

namespace App\Modules\Driver\Enums;

class AuthResponses
{
    public const INVALID_CREDENTIALS = 900;

    public const UNAUTHORIZED = 901;

    public const PHONE_VERIFICATION_REQUIRED = 902;

    public const SESSION_EXPIRED = 904;

    public const INVALID_CODE = 908;

    public const EMAIL_OR_PHONE_USED = 909;
}
