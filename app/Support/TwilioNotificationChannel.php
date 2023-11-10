<?php

namespace App\Support;

use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Notifications\Notification;
use Twilio\Exceptions\ConfigurationException;

class TwilioNotificationChannel
{
    /**
     * @var TwilioClient
     */
    protected $client;

    /**
     * TwilioNotificationChannel constructor.
     *
     * @throws ConfigurationException
     */
    public function __construct()
    {
        $twilioAccountSid   = settings("twilio_account_sid");
        $twilioAuthToken    = settings("twilio_auth_token");

        $this->client = new TwilioClient($twilioAccountSid, $twilioAuthToken);
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @return void
     * @throws TwilioException
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toTwilio($notifiable);

        $this->client->messages->create($notifiable->routeNotificationForTwilio(), [
            'from'                  => settings("twilio_from_id"),
            "body"                  => $message,
            "messagingServiceSid"   => settings('twilio_messaging_sid')
        ]);
    }
}
