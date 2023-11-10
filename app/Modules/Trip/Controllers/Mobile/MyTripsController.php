<?php

namespace App\Modules\Trip\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Modules\Trip\ApiPresenters\TripPresenter;
use App\Modules\Trip\Models\Trip;
use App\Modules\Trip\Models\TripMissingRequest;
use App\Support\Traits\ModelManipulations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MyTripsController extends Controller
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
   * UserController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Trip();
  }

  /**
   * List all user's trips
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function regular(Request $request): JsonResponse
  {
    $user = auth()->guard('user')->user();

    return success([
                     'total_pages'  => ceil(Trip::whereUserId($user->id)->whereIn('status', ['completed', 'cancelled'])->count() / 5),
                     'trips'        => Trip::whereUserId($user->id)->whereIn('status', ['completed', 'cancelled'])->orderBy('created_at', 'desc')->paginate(5)->map(function ($trip, $s) {
                       return (new TripPresenter())->item($trip);
                     })
                   ]);
  }

  /**
   * List all user's trips
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function scheduled(Request $request): JsonResponse
  {
    $user = auth()->guard('user')->user();

    return success([
                     'total_pages'  => ceil(Trip::whereUserId($user->id)->whereType('scheduled')->whereStatus('pending')->count() / 5),
                     'trips'        => Trip::whereUserId($user->id)->whereType('scheduled')->whereStatus('pending')->orderBy('created_at', 'desc')->paginate(5)->map(function ($trip) {
                       return (new TripPresenter())->scheduled($trip);
                     })
                   ]);
  }

  public function contactDriver(Request $request): JsonResponse
  {
    $this->validate($request, [
      'trip_id'     => 'required|exists:d_trips,id',
      'missing'     => 'required_if:description,|sometimes|nullable|array',
      'missing.*'   => 'required|in:phone,bag,wallet,keys',
      'description' => 'required_if:missing,|max:1000',
    ]);

    $user = auth()->guard('user')->user();

    if (!Trip::whereUserId($user->id)->find($request->get('trip_id')))
      abort(404);

    TripMissingRequest::create([
                                 'trip_id'      => $request->get('trip_id'),
                                 'missing'      => json_encode($request->get('missing')),
                                 'description'  => $request->get('description'),
                                 'user_id'      => $user->id,
                               ]);

    return success();
  }
}
