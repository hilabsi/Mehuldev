<?php

namespace App\Modules\Trip\Controllers\Mobile;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\Trip\Models\Trip;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Google\Cloud\Firestore\FieldValue;
use App\Modules\Trip\Enums\TripResponses;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
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
   * Send a chat message by user.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function sendUserMessage(Request $request)
  {
    $this->validate($request, $this->model::validations()->sendMessage());

    DB::beginTransaction();
    try {

      $user = auth()->guard('user')->user();

      $trip = Trip::whereUserId($user->id)->whereId($request->get('trip_id'))->first();

//      if($trip->status !== 'pickup' || !$trip->driver)
//        return other(TripResponses::TRIP_ALREADY_STARTED);

      $message = $trip->chat()->create([
                                         'user_id'   => $user->id,
                                         'driver_id' => $trip->driver->id,
                                         'issuer'    => 'user',
                                         'message'   => $request->get('message'),
                                       ]);

      $trip->updateFirestore([
                               ['path' => 'chat', 'value' => FieldValue::arrayUnion([[
                                 'issuer'   => $message->issuer,
                                 'message'  => $message->message,
                                 'timestamp'=> $message->created_at->timestamp
                               ]])]
                             ]);

      DB::commit();

    } catch (\Exception $exception) {
      DB::rollBack();

      return failed([$exception->getMessage()]);
    }

    notifyFCM([$trip->driver->device_id], [
      'title'  => 'New Message from User',
      'body'   => $message->message,
      'target' => 'openAppChat'
    ]);

    return success();
  }

  /**
   * Send a chat message by driver
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function sendDriverMessage(Request $request)
  {
    $this->validate($request, $this->model::validations()->sendMessage());

    DB::beginTransaction();
    try {

      $driver = auth()->guard('driver')->user();

      $trip = Trip::whereDriverId($driver->id)->whereId($request->get('trip_id'))->first();

      if($trip->status !== 'pickup')
        return other(TripResponses::TRIP_ALREADY_STARTED);

      $message = $trip->chat()->create([
                                         'driver_id'  => $driver->id,
                                         'user_id'    => $trip->user_id,
                                         'issuer'     => 'driver',
                                         'message'    => $request->get('message'),
                                       ]);

      $trip->updateFirestore([
                               ['path' => 'chat', 'value' => FieldValue::arrayUnion([[
                                 'issuer'   => $message->issuer,
                                 'message'  => $message->message,
                                 'timestamp'=> $message->created_at->timestamp
                               ]])]
                             ]);

      DB::commit();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }

    notifyFCM([$trip->user->device_id], [
      'title'  => 'New Message from Driver',
      'body'   => $message->message,
      'target' => 'openAppChat'
    ]);

    return success();
  }
}
