<?php

namespace App\Modules\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendResetPasswordCode extends Notification implements ShouldQueue
{
  use Queueable;

  /**
   * @var String
   */
  private string $code;

  /**
   * Create a new notification instance.
   *
   * @return void
   */
  public function __construct($code)
  {
    $this->code = $code;
  }

  /**
   * Get the notification's delivery channels.
   *
   * @param  mixed  $notifiable
   * @return array
   */
  public function via($notifiable)
  {
    return ['mail'];
  }

  /**
   * Get the mail representation of the notification.
   *
   * @param  mixed  $notifiable
   * @return \Illuminate\Notifications\Messages\MailMessage
   */
  public function toMail($notifiable)
  {
    return (new MailMessage)
      ->subject('Your reset password code for LOBI')
      ->line('You\'ve requested resetting your password, use this code to change it.')
      ->action($this->code, '#')
      ->line('ignore this email in case you didn\'t ask for this.' );
  }
}
