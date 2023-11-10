<?php

namespace App\Modules\PaymentMethod\Types;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WalletType
{
  /**
   * @var string
   */
  protected string $type;

  /**
   * WalletType constructor.
   * @param $type
   */
  public function __construct($type)
  {
    if (!in_array($type, ['regular', 'business']))
      throw new NotFoundHttpException('Invalid wallet');

    $this->type = $type;
  }

  /**
   * @return string
   */
  public function getType(): string
  {
    return $this->type;
  }

  public function __toString(): string
  {
    return $this->type;
  }
}
