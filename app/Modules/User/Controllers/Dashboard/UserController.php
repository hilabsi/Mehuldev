<?php

namespace App\Modules\User\Controllers\Dashboard;

use App\Modules\EmailTemplate\Models\EmailTemplate;
use App\Modules\Partner\Mails\GeneralEmail;
use App\Modules\User\Jobs\SendNotifications;
use App\Modules\User\Models\UserRating;
use App\Modules\Trip\Models\Trip;
use App\Support\Traits\TwilioActions;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\User\Models\User;
use App\Modules\User\Models\UserView;
use App\Support\Traits\ModelManipulations;
use App\Modules\User\Enums\UserResponses;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Modules\User\ApiPresenters\UserPresenter;

class UserController extends Controller
{
  use ModelManipulations;
  use Validations;
  use TwilioActions;

  /**
   *
   * @var User
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'users';

  /**
   * UserController constructor.
   */
  public function __construct ()
  {
    $this -> model = new User();
  }

  /**
   * Show all models rows.
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    $country_id = $request->get('country_id');
    if($country_id == 0)
    {
        return success([
          'rows' => User ::all()
            -> map(function ($item) {
              return (new UserPresenter()) -> item($item);
            })
        ]);
    }
    else
    {
        return success([
          'rows' => User ::where('country_id',$country_id)->get()
            -> map(function ($item) {
              return (new UserPresenter()) -> item($item);
            })
        ]);
    }
    
  }

  /**
   * Show all models rows.
   *
   * @param Request $request
   *
   * @return JsonResponse
   */
  public function search (Request $request): JsonResponse
  {
    $availableFilters = [
      // TODO
    ];

    if (!$request -> hasAny($availableFilters)) {
      return other(UserResponses::FILTER_NOT_AVAILABLE);
    }

    return success([
      'rows' => UserView ::where($request -> only($availableFilters)) -> get() -> map(function ($item) {
        return (new UserPresenter()) -> item($item);
      })
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
    //
  }

  /**
   * Fetch All User Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $user = $this -> shouldExists('id', $id);

    return success([
      'user' => (new UserPresenter()) -> item($user)
    ]);
  }


  public function disable(string $id): JsonResponse
  {
    $user = $this -> shouldExists('id', $id);
    $user -> update(['is_active'=>0]);
    return success();
  }

  public function enable(string $id): JsonResponse
  {
    $user = $this -> shouldExists('id', $id);
    $user -> update(['is_active'=>1]);
    return success();
  }


  /**
   * Update model data.
   *
   * @param String  $id
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update (string $id, Request $request): JsonResponse
  {
    $user = $this -> shouldExists('id', $id);

    $this -> validate($request, $this -> model ::validations() -> edit($user->id));

    if ($this->exists(['email' => $request->get('email')], true, $id)) {
      return other(UserResponses::USED_EMAIL);
    }

    if ($this->exists(['phone' => $request->get('phone')], true, $id)) {
      return other(UserResponses::USED_PHONE);
    }

    DB ::beginTransaction();
    try {

      $user -> update($request -> only([
        'first_name',
        'last_name',
        'email',
        'phone',
        'country_id',
        'is_phone_verified',
        'is_active',
        'password',
      ]));

      if($request->get('is_verified')){
        $user -> update(['is_phone_verified' => 1]);
      }

      if ($request->has('is_active') && ($request->get('is_active') == 0))
        try {

          $email = EmailTemplate::whereTitle('ACCOUNT_SUSPENDED_'.strtoupper($user->language->shortcut))->first() ?? EmailTemplate::whereTitle('ACCOUNT_SUSPENDED_DE')->first();

          if ($email)
            Mail::to($user)->send(new GeneralEmail(str_replace(['##first_name', '##support_link'], [$user->first_name, env('SUPPORT_URL')], $email->template), $email->subject));

        } catch (\Exception $exception) {}


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

  public function updateBusiness($id, Request $request)
  {
    $user = $this -> shouldExists('id', $id);

    $user->business()->update([
      'company_name' => $request->get('company_name'),
      'company_address' => $request->get('company_address'),
      'email' => $request->get('company_email'),
      'uid' => $request->get('uid'),
    ]);

    return success();
  }

  /**
   * Soft-delete user.
   *
   * @param $id
   * @param  Request  $request
   * @return JsonResponse
   */
  public function destroy($id, Request $request): JsonResponse
  {
    $user = User::find($id);

    if ($user->isDeleted())
      return other(UserResponses::ACCOUNT_ALREADY_DELETED);

    DB::beginTransaction();
    try {

      $user->update([
        'deleted_at' => Carbon::now(),
      ]);

      try {

        $email = EmailTemplate::whereTitle('ACCOUNT_SUSPENDED_'.strtoupper($user->language->shortcut))->first() ?? EmailTemplate::whereTitle('ACCOUNT_SUSPENDED_DE')->first();

        if ($email)
          Mail::to($user)->send(new GeneralEmail(str_replace(['##first_name', '##support_link'], [$user->first_name, env('SUPPORT_URL')], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed();
    }
  }

  public function restore($id, Request $request): JsonResponse
  {
    $user = User::find($id);

    if (!$user->isDeleted())
      return other(UserResponses::ACCOUNT_ALREADY_DELETED);

    DB::beginTransaction();
    try {

      $user->update([
        'is_deleted' => false,
        'deleted_at' => null,
      ]);

      try {

        $email = EmailTemplate::whereTitle('ACCOUNT_ACTIVATED_'.strtoupper($user->language->shortcut))->first() ?? EmailTemplate::whereTitle('ACCOUNT_ACTIVATED_DE')->first();

        if ($email)
          Mail::to($user)->send(new GeneralEmail(str_replace(['##first_name', '##app_link'], [$user->first_name, env('PLAYSTORE_URL')], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed();
    }
  }

  /**
   * Send direct notification to user.
   *
   * @param $id
   * @param  Request  $request
   * @return JsonResponse
   */
  public function sendNotification($id, Request $request): JsonResponse
  {
    $user = $this->shouldExists('id', $id);

    $this->validate($request, [
      'channels' => 'required|array',
      'channels.*' => 'in:sms,fcm,email',
      'content' => 'required|max:191',
      'title'   => 'required|max:191',
    ]);

    /** Avoiding request timeout. */
    if (in_array('fcm', $request->get('channels'))) {
      notifyFCM([$user->device_id], ['title' => $request->get('title'), 'body' => $request->get('content')]);
    }

    dispatch(new SendNotifications($request->get('channels'), 'user', $user, $request->get('title'), $request->get('content')));

    return success();
  }

  public function stats($id, Request $request): JsonResponse
  {
    $this->validate($request, [
      'type'  => 'required|in:yearly,monthly,daily',
      'year'  => 'required|digits:4',
      'month'  => 'required_if:type,monthly|max:12|min:1',
    ]);

    $user = $this->shouldExists('id', $id);
    $year = $request->get('year');
    $month = $request->get('month');
    $categories = [];
    $series = [];
    switch ($request->get('type')) {

      case 'yearly':
        $categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($categories as $index => $month) {
          $date = Carbon::createFromFormat('Y-m-d', $year. '-'.($index+1).'-01');
          $series[] = Trip::whereUserId($user->id)->whereStatus('completed')->whereYear('created_at', $date->format('Y'))->whereMonth('created_at', $date->format('m'))->sum('cost');
        }
        break;
      case 'monthly':
        $categories = ['1st Week', '2nd Week', '3rd Week', '4th Week', '5th Week'];
        $base_date = Carbon::createFromFormat('Y-m-d', $year. '-'.$month.'-01');
        foreach ($categories as $index => $week) {
          $date = clone $base_date;
          if(($index === 4) && $date->daysInMonth <= 28)
            $series[] = 0;
          else {
            $start = (clone $date)->addDays($index*7);
            $end = ($index === 4) ? (clone $date)->endOfMonth() : (clone $date)->addDays((($index+1)*7) - 1);
            $series[] = Trip::whereUserId($user->id)->whereStatus('completed')->whereBetween('created_at', [$start, $end])->sum('cost');
          }
        }
        break;
      case 'daily':
        $base_date = Carbon::createFromFormat('Y-m-d', $year. '-'.$month.'-01');
        for ($i = 1; $i <= (clone $base_date)->daysInMonth; $i++) {
          $date = (clone $base_date)->addDays($i - 1);
          $categories[] = $date->shortDayName;
          $series[] = Trip::whereUserId($user->id)->whereStatus('completed')->whereDate('created_at', $date)->sum('cost');
        }
        break;
    }

    return success([
      'series'     => $series,
      'categories' => $categories,
    ]);
  }

  public function trips($id, Request $request): JsonResponse
  {
    $user = $this->shouldExists('id', $id);

    $this->validate($request, [
      'from_date' => 'nullable|date_format:Y-m-d-H-i',
      'to_date'   => 'nullable|date_format:Y-m-d-H-i',
      'filter'    => 'in:=,>,<',
      'cost'      => 'array',
      'cost.*'    => 'numeric',
    ]);

    $query = Trip::with('driver')->where('user_id', $id);

    if ($request->get('from_date')) {
      $query->whereDate('created_at', '>=', Carbon::createFromFormat('Y-m-d-H-i', $request->get('from_date')));
    }

    if ($request->get('from_date')) {
      $query->whereDate('created_at', '=<', Carbon::createFromFormat('Y-m-d-H-i', $request->get('to_date')));
    }

    if ($request->get('cost')) {
      $query->where('cost', '>=', $request->get('cost')[0])->where('cost', '<=', $request->get('cost')[1]);
    }


    return success([
      'rows' => $query->orderBy('created_at', 'desc')->get()->map(function ($item) use ($user) {
        return [
          'id'         => $item->id,
          'driver'     => optional($item->driver)->name,
          'driver_id'  => optional($item->driver)->id,
          'rating'     => optional(UserRating::whereUserId($user->id)->whereTripId($item->id)->first())->rating ?? 'Not Rated',
          'start_time' => $item->type === 'scheduled' ? Carbon::createFromFormat('Y-m-d-H-i-s', $item->scheduled_on)->format('Y.m.d H:i:s') : $item->created_at->format('Y.m.d H:i:s'),
          'end_time'   => $item->updated_at->format('Y.m.d H:i:s'),
          'cost'       => formatNumber($item->cost),
          'numeric_cost'       => $item->cost,
          'status'     => $item->status,
          'payment_method' => $item->payment_type,
          'created_at' => $item->created_at->format('Y.m.d H:i:s'),
        ];
      })
    ]);
  }

  public function ratings($id, Request $request): JsonResponse
  {
    $user = $this->shouldExists('id', $id);

    $query = UserRating::whereUserId($id)->orderBy('created_at', 'desc');

    return success([
      'rows' => $query->orderBy('created_at', 'desc')->get()->map(function ($item) use ($user) {
        return [
          'id'         => $item->id,
          'driver'     => optional($item->driver)->name,
          'driver_id'  => optional($item->driver)->id,
          'rating'     => $item->rating,
          'trip_id'    => $item->trip_id,
          'comment'    => $item->comment ?? '-',
          'created_at' => $item->created_at->format('Y.m.d H:i:s'),
        ];
      })
    ]);
  }

  public function paymentMethodsStats($id)
  {
    $stats = Trip::whereUserId($id)->get();

    return success([
      'stats' => [
        'all'   => [
          'total'   => (clone $stats)->filter(function ($item) {return in_array(($item)->payment_type, ['cash', 'card', 'google', 'apple']);})->count(),
          'google'  => (clone $stats)->filter(function ($item) {return ($item)->payment_type === 'google';})->count(),
          'apple'   => (clone $stats)->filter(function ($item) {return ($item)->payment_type === 'apple';})->count(),
          'card'    => (clone $stats)->filter(function ($item) {return ($item)->payment_type === 'card';})->count(),
          'cash'    => (clone $stats)->filter(function ($item) {return ($item)->payment_type === 'cash';})->count()
        ],
        'completed'=> [
          'total'   => (clone $stats)->filter(function ($item) {return  in_array(optional($item)->payment_type, ['cash','card', 'google', 'apple']);})->count(),
          'google'  => (clone $stats)->filter(function ($item) {return optional($item)->payment_type === 'google';})->count(),
          'cash'    => (clone $stats)->filter(function ($item) {return optional($item)->payment_type === 'cash';})->count(),
          'apple'   => (clone $stats)->filter(function ($item) {return optional($item)->payment_type === 'apple';})->count(),
          'card'    => (clone $stats)->filter(function ($item) {return optional($item)->payment_type === 'card';})->count()
        ],
        'cancelled'=> [
          'total'   => (clone $stats)->filter(function ($item) {return (optional($item)->status === 'cancelled') &&  in_array(optional($item)->payment_type, ['cash', 'card', 'google', 'apple']);})->count(),
          'google'  => (clone $stats)->filter(function ($item) {return (optional($item)->status === 'cancelled') && optional($item)->payment_type === 'google';})->count(),
          'cash'    => (clone $stats)->filter(function ($item) {return (optional($item)->status === 'cancelled') && optional($item)->payment_type === 'cash';})->count(),
          'apple'   => (clone $stats)->filter(function ($item) {return (optional($item)->status === 'cancelled') && optional($item)->payment_type === 'apple';})->count(),
          'card'    => (clone $stats)->filter(function ($item) {return (optional($item)->status === 'cancelled') && optional($item)->payment_type === 'card';})->count()
        ],
      ],
    ]);
  }
}
