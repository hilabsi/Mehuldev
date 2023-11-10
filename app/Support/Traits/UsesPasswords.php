<?php

namespace App\Support\Traits;

use Illuminate\Support\Facades\Hash;

trait UsesPasswords
{
  /**
   * Auto-hashing new password.
   *
   * @param null $value
   */
  public function setPasswordAttribute ($value = null)
  {
    if ($value) {
      $this -> attributes['password'] = Hash ::make($value);
    }
  }

}
