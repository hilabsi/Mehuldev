<?php

namespace App\Modules\Country\Validators;

use App\Support\Contracts\ValidateModel;

class Country implements ValidateModel
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
      'name'          => 'sometimes|required|wordSpace|unique:s_countries,name,' . $id,
      'status'        => 'sometimes|required|in:enabled,disabled',
      'alpha2'        => 'sometimes|required|max:2|characters|unique:s_countries,alpha2,' . $id,
      'alpha3'        => 'sometimes|required|max:3|characters|unique:s_countries,alpha3,' . $id,
      'phone_prefix'  => 'sometimes|required|max:6|plusNumber|unique:s_countries,phone_prefix,' . $id,
      'icon'          => 'sometimes|file|image|mimes:' . settings('images_mime_types')
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
      'name'          => 'required|wordSpace|unique:s_countries,name',
      'status'        => 'required|in:enabled,disabled',
      'alpha2'        => 'required|max:2|characters|unique:s_countries,alpha2',
      'alpha3'        => 'required|max:3|characters|unique:s_countries,alpha3',
      'phone_prefix'  => 'required|max:6|plusNumber|unique:s_countries,phone_prefix',
      'icon'          => 'required|file|image|mimes:' . settings('images_mime_types'),
    ];
  }
}
