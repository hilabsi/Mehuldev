<?php


namespace App\Modules\Partner\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GeneralEmail extends Mailable
{
  use Queueable, SerializesModels;

  public String $emailBody;
  public String $emailSubject;

  /**
   * Create a new message instance.
   * @param String $emailBody
   * @param String $emailSubject
   */
  public function __construct(String $emailBody, String $emailSubject)
  {
    $this->emailBody = $emailBody;
    $this->emailSubject = $emailSubject;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {
    return $this->subject($this->emailSubject)
      ->view('Partner::mails.general-message', ['email' => $this->emailBody]);
  }
}
