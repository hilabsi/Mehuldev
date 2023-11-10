<?php

namespace App\Modules\Partner\Validators;

use App\Support\Contracts\ValidateModel;

class Partner implements ValidateModel
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
      'first_name'    => 'sometimes|required|max:191',
      'last_name'     => 'sometimes|required|max:191',
      'country_id'    => 'sometimes|required|enabled:s_countries',
      'city_id'       => 'sometimes|required|enabled:s_country_cities',
      'password'      => 'sometimes|required|min:6|max:16',
      'email'         => 'sometimes|required|email|max:191',
      'phone'         => 'sometimes|required|max:45',
      'language_id'   => 'sometimes|required|enabled:s_languages',
      'billing_type'  => 'sometimes|required|in:company,private',
      'address'       => 'sometimes|required|max:191',
      'company_name'  => 'sometimes|required_if:billing_type,company|max:191',
      'fna'           => 'sometimes|required_if:billing_type,company|max:191',
      'uid'           => 'sometimes|required_if:billing_type,company|max:191',
      'account_owner' => 'sometimes|required|max:191',
      'iban'          => 'sometimes|required',
      'bic'           => 'sometimes|required',
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
      'first_name'    => 'required|max:191',
      'last_name'     => 'required|max:191',
      'country_id'    => 'required|enabled:s_countries',
      'city_id'       => 'required|enabled:s_country_cities',
      'email'         => 'required|email|max:191',
      'phone'         => 'required|max:45',
      'language_id'   => 'required|enabled:s_languages',
      'billing_type'  => 'required|in:company,private',
      'address'       => 'required|max:191',
      'company_name'  => 'required_if:billing_type,company|max:191',
      'fna'           => 'required_if:billing_type,company|max:191',
      'uid'           => 'required_if:billing_type,company|max:191',
      'account_owner' => 'required|max:191',
      'iban'          => 'required',
      'bic'           => 'required',
    ];
  }
}
