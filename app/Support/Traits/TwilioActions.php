<?php

namespace App\Support\Traits;

use Ichtrojan\Otp\Otp;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client as TwilioClient;
use Twilio\Exceptions\ConfigurationException;

trait TwilioActions
{
  /**
   * @var TwilioClient
   */
  protected $twilio;

  /**
   * @throws ConfigurationException
   */
  public function initTwilio()
  {
    $this->twilio = new TwilioClient(settings("twilio_account_sid"), settings("twilio_auth_token"));
  }

  /**
   * Check validity of a number
   *
   * @param $number
   * @return bool
   */
  public function lookupNumber($number)
  {
    try {

      $valid = $this->twilio->lookups->v1->phoneNumbers($number)->fetch([
                                                                          'type' => 'carrier',
                                                                        ]);

    } catch (TwilioException $exception) {

      return false;
    }

    return !($valid->carrier['error_code'] || $valid->carrier['type'] !== 'mobile');
  }

  public function checkVerifyCode(Int $code, String $email, String $number)
  {
//    $this->initTwilio();

    return (new Otp)->validate($email, $code)->status;

//    return $this->twilio
//      ->verify
//      ->v2
//      ->services(env('TWILIO_VERIFICATION_SERVICE_KEY'))
//      ->verificationChecks
//      ->create($code, ["to" => $number]);
  }

  public function sendVerifyCodeBySMS($email, String $number)
  {
    $this->initTwilio();

    $code = (new Otp)->generate($email, 4, 2);

    return $this->twilio
      ->messages
      ->create($number, [
        'from'  => '+14156971259',
        'body'  => __('label.mobile.otp_message', ['code' => $code->token])
      ]);
  }

  public function sendMessage($text, $number)
  {
    try {

      $this->initTwilio();

      $resp = $this->twilio
        ->messages
        ->create($number, [
          'from'  => '+14156971259',
          'body'  => $text
        ]);

      return $resp;
    }catch (\Exception $exception) {
      return false;
    }
  }
}
