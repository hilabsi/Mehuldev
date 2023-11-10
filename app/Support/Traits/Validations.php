<?php

namespace App\Support\Traits;

use Carbon\Carbon;
use Google\ApiCore\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use CMPayments\IBAN;

trait Validations
{
  public function validateSilently(Request $request, array $rules)
  {
    try {

      $this->validate($request, $rules);

      return true;

    } catch (ValidationException $e) {

      return false;
    }
  }

  public function extendBIC()
  {
    Validator::extend('bic', function($attribute, $value, $parameters)
    {
      return preg_match('/^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/', $value);
    },__('labels.bic_message'));
  }

  public function extendIBAN()
  {
    // IBAN validation.
    Validator::extend('iban', function($attribute, $value, $parameters)
    {
      $iban = new IBAN($value);

      return $iban->validate();
    },__('labels.iban_message'));
  }

  public function extendBase64Image()
  {
    Validator::extend('base64image', function ($attribute, $value, $parameters, $validator) {
      $explode = explode(',', $value);
      $allow = ['png', 'jpg', 'svg'];
      $format = str_replace(
        [
          'data:image/',
          ';',
          'base64',
        ],
        [
          '', '', '',
        ],
        $explode[0]
      );

      // check file format
      if (!in_array($format, $allow)) {
        return false;
      }

      // check base64 format
      if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $explode[1])) {
        return false;
      }

      return true;
    },__('labels.base64image'));
  }

  public function extendBase64File()
  {
    Validator::extend('base64file', function ($attribute, $value, $parameters, $validator) {
      $explode = explode(',', $value);
      $allow = ['png'];
      $format = str_replace(
        [
          'data:image/',
          ';',
          'base64',
        ],
        [
          '', '', '',
        ],
        $explode[0]
      );

      // check file format
      if (!in_array($format, $allow)) {
        return false;
      }

      // check base64 format
      if (!preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $explode[1])) {
        return false;
      }

      return true;
    },__('labels.base64file'));
  }

  public function extentBase64()
  {
    Validator::extend('base64', function ($attribute, $value, $parameters, $validator) {
      if (preg_match('%^[a-zA-Z0-9/+]*={0,2}$%', $value)) {
        return true;
      } else {
        return false;
      }
    },__('labels.base64'));
  }

  public function extendCreditCardValidations()
  {
    if ($this->stripUserCreditCard()) {
      Validator::extend('credit_card_date', function ($attribute, $value, $parameters, $validator) {
        $currentYear = str_split(date('Y'), 2)[1];
        $dates = explode('/', $value); // separates string by a char 10/19 => [10, 19]
        return (count($dates) === 2) // should be array (validates if date is separated with / not any other char)
          && is_numeric($dates[0]) // first number should be a number
          && is_numeric($dates[1]) // second number should
          && ((int)$dates[0] >= 1  // first number(month) should be larger than or equal 1
            && (int)$dates[0] <= 12  // first number(month) should be smaller than or equal 12
            && ((int)$dates[1] >= // second number(year) should not be in past
              str_split(date('Y'), 2)[1])  // here we are separating year like (2019) to [20,19] to take second index (19) for comparing
            && (int)$dates[1] <= 99 // second number(year) should not be larger than or equal 99
            && (int)$dates[0] > ((int)$dates[1] > $currentYear ? 0 : (int)date('m')));  // first number(month) should not be in past
      },__('labels.credit_card_date'));
    }else{
      return false;
    }
  }

  public function extendStartDateValidations()
  {
    Validator::extend('start_date', function ($attribute, $value, $parameters, $validator) {
      $dates = explode('-', $value); // separates string by a char 10-19 => [10, 19]
      return (count($dates) === 3) // should be array (validates if date is separated with / not any other char)
        && is_numeric($dates[0]) // first number should be a number
        && is_numeric($dates[1]) // second number should
        && is_numeric($dates[2]) // third number should
        && ((int)$dates[2] == 1 );  // day should be 1
    },__('labels.start_date'));
  }

  public function extendEndDateValidations()
  {
    Validator::extend('end_date', function ($attribute, $value, $parameters, $validator) {
      $dates = explode('-', $value); // separates string by a char 2019-03-01 => [2019, 03, 01]
      return (count($dates) === 3) // should be array (validates if date is separated with / not any other char)
        && is_numeric($dates[0]) // first number should be a number
        && is_numeric($dates[1]) // second number should
        && is_numeric($dates[2]) // third number should
        && ((int)$dates[2] == Carbon::parse($value)->endOfMonth()->format('d'));  // day should be last day in month
    },__('labels.end_date'));
  }
}
