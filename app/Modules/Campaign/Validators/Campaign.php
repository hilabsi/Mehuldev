<?php

namespace App\Modules\Campaign\Validators;

use App\Support\Contracts\ValidateModel;

class Campaign implements ValidateModel
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
      'title'             => 'required|max:191',
      'text_title'        => 'required_if:use_push,1|max:191',
      'country_id'        => 'required|exists:s_countries,id',
      'mail_subject'      => 'required_if:use_mail,1|max:191',
      'text_message'      => 'required_if:use_sms,1|required_if:use_push,1|max:100',
      'mail_message'      => 'required_if:use_mail,1',
      'trips_activated'   => 'required|in:1,0',
      'trips_count'       => 'nullable|numeric',
      'trips_status'      => 'nullable|in:started,cancelled,aborted,completed',
      'trips_comparing'   => 'nullable|in:>,<,=',
      'user_status'       => 'required|in:active,suspended,deleted',
      'language'          => 'nullable|exists:s_languages,id',
      'has_business'      => 'required|in:1,0',
      'business_status'   => 'nullable|in:1,0',
      'use_sms'           => 'required|in:1,0',
      'use_push'          => 'required|in:1,0',
      'use_mail'          => 'required|in:1,0',
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
      'title'             => 'required|max:191',
      'text_title'        => 'required_if:use_push,1|max:191',
      'country_id'        => 'required|exists:s_countries,id',
      'mail_subject'      => 'required_if:use_mail,1|max:191',
      'text_message'      => 'required_if:use_sms,1|required_if:use_push,1|max:100',
      'mail_message'      => 'required_if:use_mail,1',
      'trips_activated'   => 'required|in:1,0',
      'trips_count'       => 'nullable|numeric',
      'trips_status'      => 'nullable|in:started,cancelled,aborted,completed',
      'trips_comparing'   => 'nullable|in:>,<,=',
      'user_status'       => 'required|in:active,suspended,deleted',
      'language'          => 'nullable|exists:s_languages,id',
      'has_business'      => 'required|in:1,0',
      'business_status'   => 'nullable|in:1,0',
      'use_sms'           => 'required|in:1,0',
      'use_push'          => 'required|in:1,0',
      'use_mail'          => 'required|in:1,0',
    ];
  }
}
