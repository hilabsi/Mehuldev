<?php

namespace App\Modules\User\Enums;

class AuthResponses
{
    public const INVALID_CREDENTIALS = 900;

    public const UNAUTHORIZED = 901;

    public const PHONE_VERIFICATION_REQUIRED = 902;

    public const DATA_NOT_COMPLETED = 903;

    public const SESSION_EXPIRED = 904;

    public const ALREADY_VERIFIED = 905;

    public const ACCOUNT_SUSPENDED = 906;

    public const MAX_ATTEMPTS_REACHED = 907;

    public const INVALID_CODE = 908;

    public const EMAIL_OR_PHONE_USED = 909;
}
