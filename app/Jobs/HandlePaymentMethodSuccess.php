<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Models\WebhookCall;

class HandlePaymentMethodSuccess implements ShouldQueue
{
  use InteractsWithQueue, Queueable, SerializesModels;

  /** @var WebhookCall */
  public WebhookCall $webhookCall;

  public function __construct(WebhookCall $webhookCall)
  {
    $this->webhookCall = $webhookCall;
  }

  public function handle()
  {
    /**
     * start trip...
     */

    Log::info($this->webhookCall);
  }
}
