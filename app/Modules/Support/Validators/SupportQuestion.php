<?php

namespace App\Modules\Support\Validators;

use App\Support\Contracts\ValidateModel;

class SupportQuestion implements ValidateModel
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
      'name'          => 'sometimes|required|max:191',
      'type'             => 'sometimes|required|in:text,webview,action',
      'link'             => 'sometimes|required_if:type,webview',
      'text'             => 'sometimes|required_if:type,text',
      'action'           => 'sometimes|required_if:type,action',
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
      'name'          => 'required|max:191',
      'type'             => 'required|in:text,webview,action',
      'link'             => 'required_if:type,webview',
      'text'             => 'required_if:type,text',
      'action'           => 'required_if:type,action',
    ];
  }
}
