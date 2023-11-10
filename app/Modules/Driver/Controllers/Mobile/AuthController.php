<?php

namespace App\Modules\Driver\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Support\Traits\TwilioActions;
use App\Modules\Driver\Models\Driver;
use App\Modules\Driver\Enums\AuthResponses;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;
use App\Modules\Driver\Models\DriverPasswordReset;
use App\Modules\Driver\Notifications\SendResetPasswordCode;

class AuthController extends Controller
{
  use TwilioActions;

  /**
   * @var Driver
   */
  protected Driver $model;

  /**
   * AuthController constructor.
   */
  public function __construct()
  {
    $this->model = new Driver;
  }

  /**
   * Generate an access token for driver by email.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function loginByEmail (Request $request) : JsonResponse
  {
    $this -> validate($request, $this -> model ::validations() -> loginByEmail());

    try {
        
      if ($token = Auth::guard('driver')->attempt($request -> only(['email', 'password']))) {
        
        $driver = Auth ::guard('driver') -> user();

        if (($driver->status !== 'active') || (!$driver->is_verified) || $driver->partner->status !== 'active')
          return other(AuthResponses::INVALID_CREDENTIALS, [
            $driver->status,
            $driver->partner,
            $driver->partner->is_deleted
          ]);

        $params = [
          'token'     => $token,
          'expiry'    => (int)Auth ::guard('driver') -> factory() -> getTTL(),
          'partner'   => $driver->partner->firestore_ref,
          'document'  => $driver->firestore_ref,
        ];

        return success($params);
      }

    } catch (\Exception $exception) {

      return failed([
                      $exception -> getCode(),
                      $exception -> getMessage()
                    ]);
    }

    return other(AuthResponses::INVALID_CREDENTIALS);
  }

  /**
   * Check reset password code and change it.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function resetPassword(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->resetPasswordCode());

    $code = DriverPasswordReset::where(['driver_id' => Auth::guard('driver')->id(), 'status' => 'pending'])->first();

    if (! $code)
      return other(AuthResponses::INVALID_CREDENTIALS);

    DB::beginTransaction();
    try {

      $code->driver->update(['password' => $request->get('password')]);

      $code->delete();

      DB::commit();

      return success();

    } catch (\Exception $e) {

      DB::rollBack();

      return failed([$e->getMessage()]);
    }
  }

  public function checkResetPasswordCode(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->checkResetPasswordCode());

    $code = DriverPasswordReset::where([
                                         'code'   => $request->get('code'),
                                         'status' => 'pending',
                                         'email'  => $request->get('email')])->first();

    if (! $code)
      return other(AuthResponses::INVALID_CODE);

    $token = JWTAuth::fromUser($code->driver);

    return success([
                     'token' => $token
                   ]);
  }

  /**
   * Request password change code.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function forgotPassword(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->forgotPassword());

    $driver = Driver::where(['email' => $request->get('email')])->first();

    if (!$driver)
      return other(AuthResponses::INVALID_CREDENTIALS);

    DB::beginTransaction();
    try {

      $driver->passwordResets()->pending()->delete();

      $code = generateRandomCodeNumber(4);

      $driver->passwordResets()->create([
                                          'code'  => $code,
                                          'email' => $driver->email
                                        ]);

      $driver->notify(new SendResetPasswordCode($code));

      DB::commit();

      return success();

    } catch (\Exception $e) {

      DB::rollBack();

      return failed([$e->getMessage(), $e->getTrace()]);
    }

  }

  /**
   * Expand token's expiry date
   *
   * @param Request $request
   *
   * @return JsonResponse
   */
  public function refreshToken (Request $request): JsonResponse
  {
    try {

      $token = JWTAuth ::refresh(JWTAuth ::getToken());

      JWTAuth ::setToken($token);

    } catch (JWTException $e) {

      return other(401);
    }

    return success([
                     'token'  => $token,
                     'expiry' => (int)Auth ::guard('driver') -> factory() -> getTTL()
                   ]);
  }
}
