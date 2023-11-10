<?php

namespace App\Modules\User\Jobs;

use App\Jobs\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use App\Support\Traits\TwilioActions;
use App\Modules\Driver\Mails\GeneralMail;

class SendNotifications extends Job
{
  use TwilioActions;

  /**
   * @var array
   */
  protected array $channels;

  protected $title, $content;

  /**
   * @var Collection
   */
  protected $user;

  /**
   * @var string
   */
  protected string $type;

  /**
   * Create a new job instance.
   *
   * @param $type
   * @param $user
   */
  public function __construct($channels, $type, $user, $title, $content)
  {
    $this->channels = $channels;
    $this->title = $title;
    $this->content = $content;
    $this->type = $type;
    $this->user = $user;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {

    if ($this->type === 'user') {
      if (in_array('sms', $this->channels)) {
        $this->sendMessage($this->content, $this->user->getFullPhoneNumber());
      }

      if (in_array('email', $this->channels)) {
        Mail::to($this->user)->send(new GeneralMail($this->title, $this->content));
      }
    }
  }

}
