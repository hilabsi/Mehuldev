<?php

namespace App\Modules\Trip\Controllers\Dashboard;

use App\Modules\Car\Models\Car;
use App\Modules\Driver\Models\Driver;
use App\Modules\Partner\Models\Partner;
use App\Modules\Trip\Models\Trip;
use App\Modules\User\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use App\Modules\Trip\ApiPresenters\TripPresenter;
use Illuminate\Http\Request;

class StatsController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var Trip
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'trips';

  /**
   * TripController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Trip();
  }

  public function users (): JsonResponse
  {
    if ($partnerId = request()->get('partner_id'))
      return success([
                       'stats' => [
                         'drivers'=> number_format(Driver::wherePartnerId($partnerId)->count(), 0, ',', '.'),
                         'cars'   => number_format(Car::wherePartnerId($partnerId)->count(), 0, ',', '.'),
                       ]
                     ]);

    return success([
                     'stats' => [
                       'users'    => number_format(User::count(), 0, ',', '.'),
                       'drivers'  => number_format(Driver::count(), 0, ',', '.'),
                       'partners'  => number_format(Partner::count(), 0, ',', '.'),
                     ]
                   ]);
  }

  public function profit (Request $request): JsonResponse
  {
    $this->validate($request, [
      'type'  => 'required|in:yearly,monthly,daily',
      'year'  => 'required|digits:4',
      'month'  => 'required_if:type,monthly|max:12|min:1',
    ]);

    $year = $request->get('year');
    $month = $request->get('month');
    $categories = [];
    $series = [];
    switch ($request->get('type')) {

      case 'yearly':
        $categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($categories as $index => $month) {
          $date = Carbon::createFromFormat('Y-m-d', $year. '-'.($index+1).'-01');
          $series[] = Trip::whereStatus('completed')->whereYear('created_at', $date->format('Y'))->whereMonth('created_at', $date->format('m'))->sum('cost');
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
            $series[] = Trip::whereStatus('completed')->whereBetween('created_at', [$start, $end])->sum('cost');
          }
        }
        break;
      case 'daily':
        $base_date = Carbon::createFromFormat('Y-m-d', $year. '-'.$month.'-01');
        for ($i = 1; $i <= (clone $base_date)->daysInMonth; $i++) {
          $date = (clone $base_date)->addDays($i - 1);
          $categories[] = $date->shortDayName;
          $series[] = Trip::whereStatus('completed')->whereDate('created_at', $date)->sum('cost');
        }
        break;
    }

    return success([
                     'series'     => $series,
                     'categories' => $categories,
                   ]);
  }

  public function status (): JsonResponse
  {
    $trips = Trip::all();

    return success([
                     'stats' => [
                       'all'        => number_format($trips->count(), 0, ',', '.'),
                       'completed'  => number_format($trips->filter(function ($item) {
                         return $item['status'] === 'completed';
                       })->count(), 0, ',', '.'),
                       'cancelled'    => number_format($trips->filter(function ($item) {
                         return in_array($item['status'], ['cancelled', 'aborted']);
                       })->count(), 0, ',', '.'),
                       'accepted' => number_format($trips->filter(function ($item) {
                         return in_array($item['status'], ['pickup', 'completed', 'started']);
                       })->count(), 0, ',', '.'),
                     ]
                   ]);
  }

  public function payments (): JsonResponse
  {
    if ($partnerId = request()->get('partner_id'))
      $trips = Trip::whereHas('driver', function ($query) use ($partnerId) {
        $query->where('partner_id', $partnerId);
      })->whereStatus('completed')->get();
    else
      $trips = Trip::whereStatus('completed')->get();

    return success([
                     'stats' => [
                       'cash'  => formatNumber($trips->filter(function ($item) {
                         return $item['payment_type'] === 'cash';
                       })->sum(function ($item) {
                         return $item['cost'];
                       })),
                       'credit'    => formatNumber($trips->filter(function ($item) {
                         return $item['payment_type'] !== 'cash';
                       })->sum(function ($item) {
                         return $item['cost'];
                       })),
                     ]
                   ]);
  }
}
