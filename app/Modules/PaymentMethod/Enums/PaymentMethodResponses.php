<?php

namespace App\Modules\PaymentMethod\Enums;

class PaymentMethodResponses
{
  public const CARD_ALREADY_ADDED = 1200;

  public const INVALID_DATE = 1201;

  public const INVALID_CARD_NUMBER = 1202;

  public const INVALID_CARD = 1203;

  public const CANNOT_REMOVE_CARD = 1204;
}
