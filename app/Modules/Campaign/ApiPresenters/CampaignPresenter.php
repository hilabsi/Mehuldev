<?php

namespace App\Modules\Campaign\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class CampaignPresenter extends ApisPresenter
{

  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present (): array
  {
    return $this -> collection -> map(function ($item) {
      return $this -> item($item);
    })
      -> toArray();
  }

  public function item ($item)
  {
    $via = [];
    if ($item->use_sms)
      $via[] = 'sms';
    if ($item->use_push)
      $via[] = 'push';
    if ($item->use_mail)
      $via[] = 'mail';

    return [
      'id'    => $item->id,
      'title' => $item->title,
      'text_title' => $item->text_title,
      'country_id' => $item->country_id,
      'mail_subject' => $item->mail_subject,
      'text_message' => $item->text_message,
      'mail_message' => $item->mail_message,
      'trips_activated' => $item->trips_activated,
      'trips_count' => $item->trips_count,
      'trips_status' => $item->trips_status,
      'trips_comparing' => $item->trips_comparing,
      'user_status' => $item->user_status,
      'language' => $item->language,
      'has_business' => $item->has_business,
      'business_status' => $item->business_status,
      'use_sms' => $item->use_sms,
      'use_push' => $item->use_push,
      'use_mail' => $item->use_mail,
      'usage' => $item->usage,
      'via' => implode(',', $via),
      'formatted_created_at' => $item->created_at->format('d.m.Y H:i:s'),
      'created_at' => $item->created_at
    ];
  }
}
