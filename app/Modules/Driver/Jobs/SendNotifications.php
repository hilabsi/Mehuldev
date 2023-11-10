<?php

namespace App\Modules\Driver\Jobs;

use App\Jobs\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use App\Support\Traits\TwilioActions;
use App\Modules\Driver\Mails\GeneralMail;
use App\Modules\Driver\Models\DriverNotification;
use Kreait\Firebase\Messaging\RawMessageFromArray;

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

    if ($this->type === 'driver') {
      if (in_array('sms', $this->channels)) {
        $this->sendMessage($this->content, $this->user->getFullPhoneNumber());
      }

      if (in_array('email', $this->channels)) {
        Mail::to($this->user)->send(new GeneralMail($this->title, $this->content));
      }
    }
  }

}
