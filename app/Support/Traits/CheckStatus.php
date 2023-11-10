<?php

namespace App\Support\Traits;

trait CheckStatus
{

  /**
   * @return bool
   */
  public function isActive()
  {
    return !!$this->is_active;
  }

  /**
   * @return bool
   */
  public function isDisabled()
  {
    return !!!$this->is_active;
  }

  /**
   * @return bool
   */
  public function isDeleted()
  {
    return !!$this->is_deleted;
  }

  /**
   * @return bool
   */
  public function isVerified()
  {
    return !!$this->is_verified;
  }

  public function disable()
  {
    $this->update(['is_active' => 0]);
  }

  public function activate()
  {
    $this->update(['is_active' => 1]);
  }
}
