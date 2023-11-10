<?php


namespace App\Modules\Partner\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationMail extends Mailable
{
  use Queueable, SerializesModels;

  public String $email;

  /**
   * Create a new message instance.
   * @param  String  $email
   * @param $code
   */
  public function __construct(String $email)
  {
    $this->email = $email;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {
    return $this->subject('LOBI: Verify your email')
      ->view('Partner::mails.registered', ['email' => $this->email]);
  }
}
