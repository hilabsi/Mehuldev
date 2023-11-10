<?php

namespace App\Modules\Trip\Controllers\Mobile;

use Twilio\Jwt\AccessToken;
use Illuminate\Http\Request;
use Twilio\TwiML\VoiceResponse;
use Twilio\Jwt\Grants\VoiceGrant;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use App\Modules\Trip\Models\Trip;
use App\Http\Controllers\Controller;
use App\Modules\Driver\Models\Driver;
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

  public function accessTokenUser(Request $request): JsonResponse
  {
    $user = auth()->guard('user')->user();

    if (! $user->currentTrip || $user->currentTrip->status !== 'pickup')
      return other(TripResponses::CALL_NOT_AVAILABLE);

    $identity = str_replace('-', '_', $user->id);

    // Create access token, which we will serialize and send to the client
    $token = new AccessToken('AC7bda696ed8b60daca18947daf161fb20',
                             'SK17914d6ed3acc90ac5cd20675f69e1f7',
                             'TRxem0HxWAKBwc1b6XJkdNAfiWgsQPvj',
                             3600,
                             $identity
    );

    // Grant access to Video
    $grant = new VoiceGrant();

    $grant->setOutgoingApplicationSid('APb74b7f4a0687dea5cd801ac3df611295');

    $grant->setIncomingAllow(true);

    $token->addGrant($grant);

    $token->addClaim('userId', $user->id);

    return success(['token' => $token->toJWT()]);
  }

  public function accessTokenDriver(Request $request): JsonResponse
  {
    $user = auth()->guard('driver')->user();

    if (! $user->currentTrip || $user->currentTrip->status !== 'pickup')
      return other(TripResponses::CALL_NOT_AVAILABLE);

    $identity = str_replace('-', '_', $user->id);

    // Create access token, which we will serialize and send to the client
    $token = new AccessToken('AC7bda696ed8b60daca18947daf161fb20',
                             'SK17914d6ed3acc90ac5cd20675f69e1f7',
                             'TRxem0HxWAKBwc1b6XJkdNAfiWgsQPvj',
                             3600,
                             $identity
    );

    // Grant access to Video
    $grant = new VoiceGrant();

    $grant->setOutgoingApplicationSid('APb74b7f4a0687dea5cd801ac3df611295');

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

    $userId = str_replace('_', '-', $req[1]);

    $user = null;
    if ($user = Driver::find($userId))
      $to = $user->currentTrip->user->getFullPhoneNumber();
    else if($user = User::find($userId))
      $to= $user->currentTrip->driver->getFullPhoneNumber();
    else
      return other(TripResponses::CALL_NOT_AVAILABLE);

    if (!$user || ! $user->currentTrip || $user->currentTrip->status !== 'pickup')
      return other(TripResponses::CALL_NOT_AVAILABLE);

    /*
     * Use a valid Twilio number by adding to your account via https://www.twilio.com/console/phone-numbers/verified
     */
    $callerNumber = '+14156971259';

    $response = new VoiceResponse();
    $response->say('Welcome to Lobi, We are connecting your safely');

    $response
      ->dial($to, [
        'callerId' => $callerNumber
      ]);

    $response->say('Thank you for using LOBI');

    return $response;
  }
}
