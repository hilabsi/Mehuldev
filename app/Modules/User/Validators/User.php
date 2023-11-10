<?php

namespace App\Modules\User\Validators;

use App\Support\Contracts\ValidateModel;

class User implements ValidateModel
{
  /**
   * Validate model on edit operation.
   *
   * @param
   *            $id
   *
   * @return array
   */
  public function edit ($id): array
  {
    return [
      'email'       => 'sometimes|required|max:191|email',
      'country_id'  => 'sometimes|required|exists:s_countries,id|enabled:s_countries',
      'first_name'  => 'sometimes|required|max:191',
      'last_name'   => 'sometimes|required|max:191',
      'phone'       => 'sometimes|required|regex:/^[0-9]+$/|digits_between:9,15|max:15',
      'photo'       => 'sometimes|file|image|mimes:' . settings('images_mime_types')
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
      'email'       => 'required|max:191|email',
      'country_id'  => 'required|exists:s_countries,id|enabled:s_countries',
      'first_name'  => 'required|max:191',
      'last_name'   => 'required|max:191',
      'phone'       => 'required|regex:/^[0-9]+$/|digits_between:9,15|max:15',
      'photo'       => 'sometimes|nullable|file|image|mimes:' . settings('images_mime_types'),
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

  /**
   * Validate model on update business profile.
   *
   * @return array
   */
  public function updateBusinessProfile (): array
  {
    return [
      'company_name'    => 'required|max:191',
      'uid'             => 'nullable|max:191',
      'company_address' => 'required|max:191',
      'email'           => 'required|email|max:191',
    ];
  }

  /**
   * Validate model on update user place.
   *
   * @return array
   */
  public function updatePlace ($userId): array
  {
    return [
      'place_id'        => 'required|owned_by_user:d_user_places,'.$userId,
      'address'         => 'required|max:191',
      'location'        => 'required|array|min:2|max:2',
      'location.*'    => 'required|numeric',
    ];
  }

  /**
   * Validate model on changing user's password.
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
   * Validate model on changing user's general data.
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
}
