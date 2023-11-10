<?php

namespace App\Modules\Campaign\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Support\Traits\TwilioActions;
use App\Modules\Campaign\Models\Campaign;
use App\Modules\Driver\Mails\GeneralMail;
use Illuminate\Database\Eloquent\Builder;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Campaign\Enums\CampaignResponses;
use App\Modules\Campaign\ApiPresenters\CampaignPresenter;

class CampaignController extends Controller
{
  use ModelManipulations;
  use TwilioActions;

  /**
   *
   * @var Campaign
   */
  protected Campaign $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'campaigns';

  /**
   * CampaignController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Campaign();
  }

  /**
   * Show all models rows.
   */
  public function index(): JsonResponse
  {
    return success([
                     'rows' => Campaign ::orderBy('created_at', 'desc')->get() -> map(function ($item) {
                       return (new CampaignPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single Campaign Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'campaign' => (new CampaignPresenter()) -> item($model)
                   ]);
  }

  /**
   * Save model data.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function store (Request $request): JsonResponse
  {
    $this -> validate($request, $this -> model ::validations() -> create());

    DB ::beginTransaction();
    try {
      $campaign = $this -> model -> create($request -> only([
                                                              'title',
                                                              'text_title',
                                                              'country_id',
                                                              'mail_subject',
                                                              'text_message',
                                                              'mail_message',
                                                              'trips_activated',
                                                              'trips_count',
                                                              'trips_status',
                                                              'trips_comparing',
                                                              'user_status',
                                                              'language',
                                                              'has_business',
                                                              'business_status',
                                                              'use_sms',
                                                              'use_push',
                                                              'use_mail',
                                                              'usage',
                                                            ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $campaign -> id
                   ]);
  }

  /**
   * Update model data.
   *
   * @param Int     $id
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update (int $id, Request $request): JsonResponse
  {
    $this -> validate($request, $this -> model ::validations() -> edit($id));

    $user = $this -> shouldExists('id', $id);

    if (!$request -> hasAny([
                              'title',
                              'text_title',
                              'country_id',
                              'mail_subject',
                              'text_message',
                              'mail_message',
                              'trips_activated',
                              'trips_count',
                              'trips_status',
                              'trips_comparing',
                              'user_status',
                              'language',
                              'has_business',
                              'business_status',
                              'use_sms',
                              'use_push',
                              'use_mail',
                              'usage',
                            ])) {
      return other(CampaignResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {
      $user -> update($request -> only([
                                         'title',
                                         'text_title',
                                         'country_id',
                                         'mail_subject',
                                         'text_message',
                                         'mail_message',
                                         'trips_activated',
                                         'trips_count',
                                         'trips_status',
                                         'trips_comparing',
                                         'user_status',
                                         'language',
                                         'has_business',
                                         'business_status',
                                         'use_sms',
                                         'use_push',
                                         'use_mail',
                                         'usage',
                                       ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getCode(),
                      $exception -> getMessage()
                    ]);
    }

    return success();
  }

  public function destroy(int $id): JsonResponse
  {
    $model = $this->shouldExists('id', $id);

    $model->delete();

    return success();
  }

  public function send(int $id): JsonResponse
  {
    $campaign = $this->shouldExists('id', $id);

    $target = User::whereIsActive($campaign->user_status === 'active' ? 1 : 0);

    if ($campaign->trips_activated) {
      $target = $target->whereHas('trips', function (Builder $query) use ($campaign) {
        return $query->where('status', $campaign->trips_status);
      }, $campaign->trips_comparing, $campaign->trips_count);
    }

    if ($campaign->language)
      $target = $target->where('language_id', $campaign->language);

    if ($campaign->has_card)
      if ($campaign->card_status)
        $target = $target->has('cards', '>=', 1);
      else
        $target = $target->has('cards', '<', 1);

    if ($campaign->has_business)
      if ($campaign->business_status)
        $target = $target->whereHas('business', function (Builder $query) {
          return $query->whereNotNull('company_name');
        });
      else
        $target = $target->whereHas('business', function (Builder $query) {
          return $query->whereNull('company_name');
        });

    $users = $target->get();
    $count = $users->count();

    foreach ($users as $user) {
      $replacement = [
        '^first_name' => $user->first_name,
        '^last_name'  => $user->last_name,
      ];

      if ($campaign->use_sms) {
        $text = str_replace(array_keys($replacement), array_values($replacement), $campaign->text_message);

        try {
          $this->sendMessage($text, $user->getFullPhoneNumber());
        } catch (\Exception $e) {
          Log::error('campaign sms message failed when sending to: '. $user->id);
        }
      }

      if ($campaign->use_push) {
        $title = str_replace(array_keys($replacement), array_values($replacement), $campaign->text_title);
        $text = str_replace(array_keys($replacement), array_values($replacement), $campaign->text_message);

        try {
          notifyFCM([$user->device_id], ['title'=> $title, 'body' => $text]);
        } catch (\Exception $e) {
          Log::error('campaign sms message failed when sending to: '. $user->id);
        }
      }

      if ($campaign->use_mail) {
        $subject = str_replace(array_keys($replacement), array_values($replacement), $campaign->mail_subject);
        $mail = str_replace(array_keys($replacement), array_values($replacement), $campaign->mail_message);

        try {
          Mail::to($user)->send(new GeneralMail($subject, $mail));
        } catch (\Exception $e) {
          Log::error('campaign sms message failed when sending to: '. $user->id);
        }
      }
    }

    $campaign->increment('usage', );

    $via = [];
    if ($campaign->use_sms)
      $via[] = 'sms';
    if ($campaign->use_push)
      $via[] = 'push';
    if ($campaign->use_mail)
      $via[] = 'mail';

    $campaign->usage()->create(['users' => $count, 'via' => implode(',', $via)]);

    return success();
  }
}
