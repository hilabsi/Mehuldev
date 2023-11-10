<?php

namespace App\Modules\Driver\Validators;

use App\Support\Contracts\ValidateModel;

class Driver implements ValidateModel
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
      // TODO
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
      // TODO
    ];
  }


  /**
   * @return string[]
   */
  public function loginByEmail(): array
  {
    return [
      'email'   => 'required|email|max:191',
      'password'=> 'required',
    ];
  }

  /**
   * @return string[]
   */
  public function forgotPassword(): array
  {
    return [
      'email' => 'required|email|max:191',
    ];
  }

  /**
   * Validate model on changing driver's password.
   *
   * @return array
   */
  public function changePassword (): array
  {
    return [
      'new_password'  => 'required|max:16|min:6',
    ];
  }

  /**
   * Validate model on changing driver's general data.
   *
   * @return array
   */
  public function updateProfile (): array
  {
    return [
      'email'       => 'sometimes|required|max:191|email',
      'first_name'  => 'sometimes|required|max:191',
      'last_name'   => 'sometimes|required|max:191',
      'picture'     => 'sometimes|nullable|file|image|mimes:' . settings('images_mime_types')
    ];
  }

  /**
   * @return string[]
   */
  public function resetPasswordCode(): array
  {
    return [
      'password'=> 'required|min:6|max:16', // TODO: get criteria from mobile.
    ];
  }
  /**
   * @return string[]
   */
  public function checkResetPasswordCode(): array
  {
    return [
      'email'   => 'required|email',
      'code'    => 'required|numeric|digits:4',
    ];
  }

  /**
   * @return string[]
   */
  public function checkVerification(): array
  {
    return [
      'code'    => 'required|numeric|digits:4',
    ];
  }

  /**
   * Validate model on update device id.
   *
   * @return array
   */
  public function updateDeviceId (): array
  {
    return [
      'device_id' => 'required'
    ];
  }

  /**
   * Validate model on update language id.
   *
   * @return array
   */
  public function updateLanguage (): array
  {
    return [
      'language_id' => 'required|enabled:s_languages',
    ];
  }

  public function updateLocation()
  {
    return [
      'location'     => 'required',
      'location.lat' => 'required|numeric',
      'location.lng' => 'required|numeric',
    ];
  }
}
