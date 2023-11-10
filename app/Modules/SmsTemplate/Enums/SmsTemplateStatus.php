<?php

namespace App\Modules\SmsTemplate\Enums;

use App\Support\Exceptions\InvalidEnumerationException;

class SmsTemplateStatus
{

  public const ENABLED = 'enabled', DISABLED = 'disabled';

  /**
   * Check if value exists in enum.
   *
   * @param
   *            $value
   *
   * @throws InvalidEnumerationException
   */
  public static function includes ($value)
  {
    if (!in_array(strtolower($value), [
      self::ENABLED,
      self::DISABLED
    ])) {
      throw new InvalidEnumerationException();
    }
  }
}
