<?php


namespace App\Modules\Driver\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GeneralMail extends Mailable
{
  use Queueable, SerializesModels;

  public String $title;
  public String $text;

  /**
   * Create a new message instance.
   *
   * @param  String  $title
   * @param  String  $text
   */
  public function __construct(String $title ,String $text)
  {
    $this->title = $title;
    $this->text = $text;
  }

  /**
   * Build the message.
   *
   * @return $this
   */
  public function build()
  {
    return $this->subject($this->title)
      ->view('Driver::mails.general', ['text' => $this->title]);
  }
}
