<?php

namespace App\Modules\Support\Controllers\Mobile;

use Twilio\Jwt\AccessToken;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\Grants\VoiceGrant;
use Illuminate\Http\JsonResponse;
use App\Modules\Trip\Models\Trip;
use App\Http\Controllers\Controller;
use App\Modules\Trip\Enums\TripResponses;
use App\Support\Traits\ModelManipulations;

class CallsController extends Controller
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
  protected $type = 'trip-calls';

  /**
   * UserController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Trip();
  }

  public function accessToken(Request $request): JsonResponse
  {
    $this->validate($request, [
      'trip_id' => 'required|exists:d_trips,id'
    ]);

    $user = auth()->guard('user')->user();
    $trip = Trip::find($request->get('trip_id'));

    if (! ($user->id === $trip->user_id) || $trip->status !== 'completed')
      return other(TripResponses::CALL_NOT_AVAILABLE);

    $identity = str_replace('-', '_', $trip->id);

    // Create access token, which we will serialize and send to the client
    $token = new AccessToken('AC7bda696ed8b60daca18947daf161fb20',
                             'SK17914d6ed3acc90ac5cd20675f69e1f7',
                             'TRxem0HxWAKBwc1b6XJkdNAfiWgsQPvj',
                             3600,
                             $identity
    );

    // Grant access to Video
    $grant = new VoiceGrant();

    $grant->setOutgoingApplicationSid('AP5998bb2da9cd09f1e37b6d102d14d220');

    $grant->setIncomingAllow(true);

    $token->addGrant($grant);

    return success(['token' => $token->toJWT()]);
  }

  public function incoming(Request $request)
  {
    $req = explode(':', $request->get('From'));

    if (count($req) < 2) {
      $response = new VoiceResponse();
      $response->reject();
      return $response;
    }

    $tripId = str_replace('_', '-', $req[1]);

    $trip = Trip::find($tripId);

    if (!$trip || !$trip->driver || $trip->status !== 'completed')
      return other(TripResponses::CALL_NOT_AVAILABLE);

    /*
     * Use a valid Twilio number by adding to your account via https://www.twilio.com/console/phone-numbers/verified
     */
    $callerNumber = '+14156971259';

    $response = new VoiceResponse();
    $response->say('Welcome to Lobi, We are connecting your safely');

    $response
      ->dial($trip->driver->getFullPhoneNumber(), [
        'callerId' => $callerNumber
      ]);

    $response->say('Thank you for using LOBI');

    return $response;
  }
}
