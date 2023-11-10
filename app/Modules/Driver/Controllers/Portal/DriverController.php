<?php

namespace App\Modules\Driver\Controllers\Portal;

use App\Modules\EmailTemplate\Models\EmailTemplate;
use App\Modules\Partner\Mails\GeneralEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Modules\Trip\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Driver\Models\Driver;
use App\Support\Traits\TwilioActions;
use App\Modules\Trip\Models\TripRequest;
use App\Modules\Driver\Models\DriverView;
use App\Support\Traits\ModelManipulations;
use App\Modules\Driver\Models\DriverRating;
use App\Modules\Driver\Enums\DriverResponses;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Modules\Driver\Jobs\SendNotifications;
use App\Modules\Driver\Models\DriverNotification;
use App\Modules\Driver\ApiPresenters\DriverPresenter;

class DriverController extends Controller
{
  use ModelManipulations;
  use Validations;
  use TwilioActions;

  /**
   *
   * @var Driver
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'drivers';

  /**
   * DriverController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Driver();
  }

  /**
   * Show all models rows.
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    $partner = auth()->guard('partner')->user();

    if ($request->get('active'))
      return success([
        'rows' => (Driver::wherePartnerId($partner->id)->whereIsVerified(1)->whereStatus('active')->get())
          -> map(function ($item) {
            return (new DriverPresenter()) -> item($item);
          })
      ]);
    return success([
      'rows' => ($request->get('partner_id') ? Driver::wherePartnerId($partner->id)->get() : Driver ::wherePartnerId($partner->id)->get())
        -> map(function ($item) {
          return (new DriverPresenter()) -> item($item);
        })
    ]);
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
      return other(DriverResponses::FILTER_NOT_AVAILABLE);
    }

    return success([
      'rows' => DriverView ::where($request -> only($availableFilters)) -> get() -> map(function ($item) {
        return (new DriverPresenter()) -> item($item);
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
    $this -> validate($request, $this -> model ::validations() -> create());

    $partnerId = auth()->guard('partner')->id();

    if ($this->exists(['email' => $request->get('email')], true)) {
      return other(DriverResponses::USED_EMAIL);
    }

    if ($this->exists(['phone' => $request->get('phone')], true)) {
      return other(DriverResponses::USED_PHONE);
    }

    DB ::beginTransaction();
    try {

      $driver = $this -> model -> create(array_merge($request -> only([
        'first_name',
        'last_name',
        'phone',
        'license_type_id',
        'password',
        'email',
        'country_id',
        'city_id',
        'birthday',
        'gender',
        'id_type',
        'id_number',
        'license_number'
      ]), [
        'partner_id' => $partnerId
      ]));

      $driver->setDocuments($request->allFiles());

      $driver->configureFirestore();

      try {

        $email = EmailTemplate::whereTitle('PARTNER_DRIVER_ADDED_'.strtoupper($driver->partner->language->shortcut))->first() ?? EmailTemplate::whereTitle('PARTNER_DRIVER_ADDED_DE')->first();

        if ($email)
          Mail::to($driver->partner)->send(new GeneralEmail(str_replace(['##first_name', '##portal_url', '##driver_name'], [$driver->partner->first_name, env('PORTAL_URL', 'ttps://portal.lobi.at/'), $driver->first_name . ' ' .$driver->last_name], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      try {

        $email = EmailTemplate::whereTitle('DRIVER_ACCOUNT_ACTIAVTED_'.strtoupper($driver->language->shortcut))->first() ?? EmailTemplate::whereTitle('DRIVER_ACCOUNT_ACTIAVTED_DE')->first();

        if ($email)
          Mail::to($driver->partner)->send(new GeneralEmail(str_replace(['##first_name', '##portal_url'], [$driver->first_name, env('PORTAL_URL', 'https://portal.lobi.at/')], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      DB ::commit();

    } catch (Exception $exception) {

      DB ::rollBack();

      return failed([
        $exception -> getMessage(),
        $exception->getTrace()
      ]);
    }

    return success([
      'id' => $driver -> id
    ]);
  }

  /**
   * Fetch All Driver Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $driver = $this -> shouldExists('id', $id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    return success([
      'driver' => (new DriverPresenter()) -> item($driver)
    ]);
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
    $driver = $this -> shouldExists('id', $id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    $this -> validate($request, $this -> model ::validations() -> edit($driver->id));

    if ($this->exists(['email' => $request->get('email')], true, $id)) {
      return other(DriverResponses::USED_EMAIL);
    }

    if ($this->exists(['phone' => $request->get('phone')], true, $id)) {
      return other(DriverResponses::USED_PHONE);
    }

    if (!$request -> hasAny([
      'first_name',
      'last_name',
      'phone',
      'license_type_id',
      'password',
      'email',
      'country_id',
      'city_id',
      'birthday',
      'gender',
      'status',
      'id_type',
      'id_number',
      'license_number',
      'profile',
      'id_image',
      'license_back',
      'license_front',
    ])) {
      return other(DriverResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {

      $driver -> update($request -> only([
        'first_name',
        'last_name',
        'phone',
        'status',
        'license_type_id',
        'password',
        'email',
        'country_id',
        'city_id',
        'birthday',
        'gender',
        'id_type',
        'id_number',
        'license_number'
      ]));

      $driver->setDocuments($request->allFiles());

      $driver->refresh();

      $driver->refreshFirestore();

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

  /**
   * Soft-delete driver.
   *
   * @param $id
   * @param  Request  $request
   * @return JsonResponse
   */
  public function destroy($id, Request $request): JsonResponse
  {
    $driver = Driver::find($id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    if ($driver->isDeleted())
      return other(DriverResponses::ACCOUNT_ALREADY_DELETED);

    DB::beginTransaction();
    try {

      $driver->update([
        'is_deleted' => true,
      ]);

      $driver->delete();

      try {

        $email = EmailTemplate::whereTitle('PARTNER_DRIVER_REMOVED_'.strtoupper($driver->partner->language->shortcut))->first() ?? EmailTemplate::whereTitle('PARTNER_DRIVER_REMOVED_DE')->first();

        if ($email)
          Mail::to($driver->partner)->send(new GeneralEmail(str_replace(['##first_name', '##portal_url', '##driver_name'], [$driver->partner->first_name, env('PORTAL_URL', 'ttps://portal.lobi.at/'), $driver->first_name . ' ' .$driver->last_name], $email->template), $email->subject));

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
    $driver = Driver::find($id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    if (!$driver->isDeleted())
      return other(DriverResponses::ACCOUNT_ALREADY_DELETED);

    DB::beginTransaction();
    try {

      $driver->update([
        'is_deleted' => false,
        'deleted_at' => null,
      ]);

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
    $driver = $this->shouldExists('id', $id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    $this->validate($request, [
      'channels' => 'required|array',
      'channels.*' => 'in:sms,fcm,email',
      'content' => 'required|max:191',
      'title'   => 'required|max:191',
    ]);

    /** Avoiding request timeout. */
    if (in_array('fcm', $request->get('channels'))) {
      notifyFCM([$driver->device_id], ['title' => $request->get('title'), 'body' => $request->get('content')]);
      DriverNotification::create([
        'driver_id' => $driver->id,
        'title' => $request->get('title'),
        'description' => $request->get('content'),
      ]);
    }

    dispatch(new SendNotifications($request->get('channels'), 'driver', $driver, $request->get('title'), $request->get('content')));

    return success();
  }

  public function stats($id, Request $request): JsonResponse
  {
    $this->validate($request, [
      'type'  => 'required|in:yearly,monthly,daily',
      'year'  => 'required|digits:4',
      'month'  => 'required_if:type,monthly|max:12|min:1',
    ]);

    $driver = $this->shouldExists('id', $id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    $year = $request->get('year');
    $month = $request->get('month');
    $categories = [];
    $series = [];
    switch ($request->get('type')) {

      case 'yearly':
        $categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($categories as $index => $month) {
          $date = Carbon::createFromFormat('Y-m-d', $year. '-'.($index+1).'-01');
          $series[] = Trip::whereDriverId($driver->id)->whereStatus('completed')->whereYear('created_at', $date->format('Y'))->whereMonth('created_at', $date->format('m'))->count();
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
            $series[] = Trip::whereDriverId($driver->id)->whereStatus('completed')->whereBetween('created_at', [$start, $end])->count();
          }
        }
        break;
      case 'daily':
        $base_date = Carbon::createFromFormat('Y-m-d', $year. '-'.$month.'-01');
        for ($i = 1; $i <= (clone $base_date)->daysInMonth; $i++) {
          $date = (clone $base_date)->addDays($i - 1);
          $categories[] = $date->shortDayName;
          $series[] = Trip::whereDriverId($driver->id)->whereStatus('completed')->whereDate('created_at', $date)->count();
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
    $driver = $this->shouldExists('id', $id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    $this->validate($request, [
      'from_date' => 'nullable|date_format:Y-m-d-H-i',
      'to_date'   => 'nullable|date_format:Y-m-d-H-i',
      'filter'    => 'in:=,>,<',
      'cost'      => 'array',
      'cost.*'    => 'numeric',
    ]);

    $query = Trip::whereDriverId($id);

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
      'rows' => $query->get()->map(function ($item) use ($driver) {
        return [
          'id'         => $item->id,
          'user'       => optional($item->user)->full_name,
          'rating'     => optional(DriverRating::whereDriverId($driver->id)->whereTripId($item->id)->first())->rating ?? 'Not Rated',
          'partner'    => $driver->partner->company_name,
          'start_time' => $item->type === 'scheduled' ? Carbon::createFromFormat('Y-m-d-H-i-s', $item->scheduled_on)->format('Y.m.d H:i:s') : $item->created_at->format('Y.m.d H:i:s'),
          'end_time'   => $item->updated_at->format('Y.m.d H:i:s'),
          'cost'       => formatNumber($item->cost),
          'numeric_cost'  => $item->cost,
          'status'     => $item->status,
          'payment_method' => $item->payment_type,
          'created_at' => $item->created_at->format('Y.m.d'),
        ];
      })
    ]);
  }

  public function reports(Request $request): JsonResponse
  {
    $partnerId = auth()->guard('partner')->id();

    $this->validate($request, [
      'from_date' => 'nullable|date_format:Y-m-d-H-i',
      'to_date'   => 'nullable|date_format:Y-m-d-H-i',
    ]);

    $drivers = Driver::wherePartnerId($partnerId);
    $trips = Trip::whereStatus('completed');

    if ($request->get('from_date')) {
      $trips->whereDate('created_at', '>=', Carbon::createFromFormat('Y-m-d-H-i', $request->get('from_date')));
    }

    if ($request->get('from_date')) {
      $trips->whereDate('created_at', '=<', Carbon::createFromFormat('Y-m-d-H-i', $request->get('to_date')));
    }

    return success([
      'rows' => $drivers->get()->map(function ($item) use ($trips) {
        return [
          'id'         => $item->id,
          'driver'     => $item->name,
          'rating'     => optional(DriverRating::whereDriverId($item->id)->whereIn('trip_id', (clone $trips)->pluck('id')->toArray())->first())->rating ?? 'Not Rated',
          'cash'       => (clone $trips)->whereDriverId($item->id)->wherePaymentType('cash')->sum('cost'),
          'apple'      => (clone $trips)->whereDriverId($item->id)->wherePaymentType('apple')->sum('cost'),
          'google'     => (clone $trips)->whereDriverId($item->id)->wherePaymentType('google')->sum('cost'),
          'card'       => (clone $trips)->whereDriverId($item->id)->wherePaymentType('card')->sum('cost'),
          'trips_no'   => (clone $trips)->whereDriverId($item->id)->count(),
          'created_at' => $item->created_at->format('Y.m.d'),
        ];
      })
    ]);
  }

  public function ratings($id, Request $request): JsonResponse
  {
    $driver = $this->shouldExists('id', $id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    $query = DriverRating::whereDriverId($id)->orderBy('created_at', 'desc');

    return success([
      'rows' => $query->orderBy('created_at', 'desc')->get()->map(function ($item) use ($driver) {
        return [
          'id'         => $item->id,
          'user'       => optional($item->user)->full_name,
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
    $driver = $this->shouldExists('id', $id);

    $partnerId = auth()->guard('partner')->id();

    if ($driver->partner_id !== $partnerId)
      abort(404);

    $stats = TripRequest::with('trip')->whereDriverId($id)->get();

    return success([
      'stats' => [
        'all'   => [
          'total'   => $stats->filter(function ($item) {return in_array(optional($item->trip)->payment_type, ['cash', 'card', 'google', 'apple']);})->count(),
          'google'  => $stats->filter(function ($item) {return optional($item->trip)->payment_type === 'google';})->count(),
          'apple'   => $stats->filter(function ($item) {return optional($item->trip)->payment_type === 'apple';})->count(),
          'card'    => $stats->filter(function ($item) {return optional($item->trip)->payment_type === 'card';})->count(),
          'cash'    => $stats->filter(function ($item) {return optional($item->trip)->payment_type === 'cash';})->count()
        ],
        'accepted'=> [
          'total'   => $stats->filter(function ($item) {return ($item->status === 'accepted') &&  in_array(optional($item->trip)->payment_type, ['cash','card', 'google', 'apple']);})->count(),
          'cash'    => $stats->filter(function ($item) {return ($item->status === 'accepted') && optional($item->trip)->payment_type === 'cash';})->count(),
          'google'  => $stats->filter(function ($item) {return ($item->status === 'accepted') && optional($item->trip)->payment_type === 'google';})->count(),
          'apple'   => $stats->filter(function ($item) {return ($item->status === 'accepted') && optional($item->trip)->payment_type === 'apple';})->count(),
          'card'    => $stats->filter(function ($item) {return ($item->status === 'accepted') && optional($item->trip)->payment_type === 'card';})->count()
        ],
        'completed'=> [
          'total'   => $stats->filter(function ($item) {return (optional($item->trip)->status === 'completed') &&  in_array(optional($item->trip)->payment_type, ['cash','card', 'google', 'apple']);})->count(),
          'google'  => $stats->filter(function ($item) {return (optional($item->trip)->status === 'completed') && optional($item->trip)->payment_type === 'google';})->count(),
          'cash'   => $stats->filter(function ($item) {return (optional($item->trip)->status === 'completed') && optional($item->trip)->payment_type === 'cash';})->count(),
          'apple'   => $stats->filter(function ($item) {return (optional($item->trip)->status === 'completed') && optional($item->trip)->payment_type === 'apple';})->count(),
          'card'    => $stats->filter(function ($item) {return (optional($item->trip)->status === 'completed') && optional($item->trip)->payment_type === 'card';})->count()
        ],
        'cancelled'=> [
          'total'   => $stats->filter(function ($item) {return (optional($item->trip)->status === 'cancelled') &&  in_array(optional($item->trip)->payment_type, ['cash', 'card', 'google', 'apple']);})->count(),
          'google'  => $stats->filter(function ($item) {return (optional($item->trip)->status === 'cancelled') && optional($item->trip)->payment_type === 'google';})->count(),
          'cash'  => $stats->filter(function ($item) {return (optional($item->trip)->status === 'cancelled') && optional($item->trip)->payment_type === 'cash';})->count(),
          'apple'   => $stats->filter(function ($item) {return (optional($item->trip)->status === 'cancelled') && optional($item->trip)->payment_type === 'apple';})->count(),
          'card'    => $stats->filter(function ($item) {return (optional($item->trip)->status === 'cancelled') && optional($item->trip)->payment_type === 'card';})->count()
        ],
        'rejected'=> [
          'total'   => $stats->filter(function ($item) {return ($item->status === 'rejected') &&  in_array(optional($item->trip)->payment_type, ['card', 'google', 'apple']);})->count(),
          'google'  => $stats->filter(function ($item) {return ($item->status === 'rejected') && optional($item->trip)->payment_type === 'google';})->count(),
          'cash'  => $stats->filter(function ($item) {return ($item->status === 'rejected') && optional($item->trip)->payment_type === 'cash';})->count(),
          'apple'   => $stats->filter(function ($item) {return ($item->status === 'rejected') && optional($item->trip)->payment_type === 'apple';})->count(),
          'card'    => $stats->filter(function ($item) {return ($item->status === 'rejected') && optional($item->trip)->payment_type === 'card';})->count()
        ],
        'ignored'=> [
          'total'   => $stats->filter(function ($item) {return ($item->status === 'ignored') &&  in_array(optional($item->trip)->payment_type, ['cash', 'card', 'google', 'apple']);})->count(),
          'google'  => $stats->filter(function ($item) {return ($item->status === 'ignored') && optional($item->trip)->payment_type === 'google';})->count(),
          'cash'  => $stats->filter(function ($item) {return ($item->status === 'ignored') && optional($item->trip)->payment_type === 'cash';})->count(),
          'apple'   => $stats->filter(function ($item) {return ($item->status === 'ignored') && optional($item->trip)->payment_type === 'apple';})->count(),
          'card'    => $stats->filter(function ($item) {return ($item->status === 'ignored') && optional($item->trip)->payment_type === 'card';})->count()
        ],
      ],
    ]);
  }
}
