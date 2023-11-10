<?php

namespace App\Modules\Trip\Controllers\Portal;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Modules\Car\Models\Car;
use App\Modules\Trip\Models\Trip;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Modules\Driver\Models\Driver;
use App\Support\Traits\ModelManipulations;

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
    $partner = auth()->guard('partner')->user();

    return success([
                     'stats' => [
                       'drivers'  => number_format(Driver::wherePartnerId($partner->id)->count(), 0, ',', '.'),
                       'cars'     => number_format(Car::wherePartnerId($partner->id)->count(), 0, ',', '.'),
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
    $partner = auth()->guard('partner')->user();

    switch ($request->get('type')) {

      case 'yearly':
        $categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'July', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        foreach ($categories as $index => $month) {
          $date = Carbon::createFromFormat('Y-m-d', $year. '-'.($index+1).'-01');
          $series[] = Trip::whereStatus('completed')->whereHas('driver', function ($query) use ($partner) {
            $query->where('partner_id', $partner->id);
          })->whereStatus('completed')->whereYear('created_at', $date->format('Y'))->whereMonth('created_at', $date->format('m'))->sum('cost');
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
            $series[] = Trip::whereStatus('completed')->whereHas('driver', function ($query) use ($partner) {
              $query->where('partner_id', $partner->id);
            })->whereStatus('completed')->whereBetween('created_at', [$start, $end])->sum('cost');
          }
        }
        break;
      case 'daily':
        $base_date = Carbon::createFromFormat('Y-m-d', $year. '-'.$month.'-01');
        for ($i = 1; $i <= (clone $base_date)->daysInMonth; $i++) {
          $date = (clone $base_date)->addDays($i - 1);
          $categories[] = $date->shortDayName;
          $series[] = Trip::whereStatus('completed')->whereHas('driver', function ($query) use ($partner) {
            $query->where('partner_id', $partner->id);
          })->whereStatus('completed')->whereDate('created_at', $date)->sum('cost');
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
    $partner = auth()->guard('partner')->user();

    $trips = Trip::whereStatus('completed')->whereHas('driver', function ($query) use ($partner) {
      $query->where('partner_id', $partner->id);
    })->get();

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
    $partner = auth()->guard('partner')->user();

    $trips = Trip::whereStatus('completed')->whereHas('driver', function ($query) use ($partner) {
      $query->where('partner_id', $partner->id);
    })->get();

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
