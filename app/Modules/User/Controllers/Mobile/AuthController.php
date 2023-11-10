<?php

namespace App\Modules\User\Controllers\Mobile;

use App\Modules\EmailTemplate\Models\EmailTemplate;
use App\Modules\Partner\Mails\GeneralEmail;
use App\Modules\Partner\Mails\RegistrationMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\User\Models\User;
use App\Support\Enums\TwilioCodes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Support\Traits\TwilioActions;
use Twilio\Exceptions\TwilioException;
use Laravel\Socialite\Facades\Socialite;
use App\Modules\User\Models\PendingUser;
use App\Modules\User\Enums\AuthResponses;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Modules\User\Models\UserPasswordReset;
use App\Modules\User\Models\UserSocialAccount;
use Illuminate\Validation\ValidationException;
use App\Modules\User\Notifications\SendResetPasswordCode;

class AuthController extends Controller
{
  use TwilioActions;

  /**
   * @var User
   */
  protected User $model;

  /**
   * AuthController constructor.
   */
  public function __construct()
  {
    $this->model = new User;
  }

  /**
   * Generate an access token for user by email.
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

      if ($token = JWTAuth ::attempt($request -> only(['email', 'password']))) {

        $user = Auth ::guard('user') -> user();

        if (!$user->is_active)
          return other(AuthResponses::INVALID_CREDENTIALS);

        if (!$user->is_phone_verified)
          return other(AuthResponses::PHONE_VERIFICATION_REQUIRED, [
            'token'     => $token,
            'phone'     => $user->getFullPhoneNumber(),
            'document'  => $user->firestore_ref,
            'remaining' => 3 - $user->phone_verification_attempts
          ]);

        $params = [
          'token'   => $token,
          'expiry'  => (int)Auth ::guard('user') -> factory() -> getTTL(),
          'document'  => $user->firestore_ref,
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
   * Login via Social Account
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function loginBySocialAccount(Request $request): JsonResponse
  {
    $this->validate($request, [
      'access_token'=> 'required',
      'provider'    => 'required|in:facebook,google,apple',
      'first_name'  => 'nullable|max:191',
      'last_name'   => 'nullable|max:191',
      'email'       => 'nullable|email|max:191',
    ]);

    try {

      $userSocialAccount = Socialite::driver($request->get('provider') === 'apple' ? 'sign-in-with-apple' : $request->get('provider'))
        ->userFromToken($request->get('access_token'));

    } catch (\Exception $exception) {

      return other(AuthResponses::SESSION_EXPIRED, [$exception->getMessage()]);
    }

    if ($account = UserSocialAccount::where("{$request->get('provider')}_id", $userSocialAccount->getId())->first()) {

      $user = $account->user;

      $token = JWTAuth ::fromUser($account->user);

      if (!$user->is_active)
        return other(AuthResponses::ACCOUNT_SUSPENDED);

      if (!$user->is_phone_verified)
        return other(AuthResponses::PHONE_VERIFICATION_REQUIRED, [
          'token'     => $token,
          'document'  => $user->firestore_ref,
          'phone'     => $user->getFullPhoneNumber(),
          'remaining' => 3 - $user->phone_verification_attempts
        ]);


      return success([
        'token'   => $token,
        'document'  => $user->firestore_ref,
        'expiry'  => (int)Auth ::guard('user') -> factory() -> getTTL(),
      ]);
    }

    // check if user is pending
    if (! ($pendingUser = PendingUser::whereProvider($request->get('provider'))
      ->whereProviderId($userSocialAccount->getId())->first())) {

      $pendingUser = PendingUser::create(array_merge($request->only(['first_name', 'last_name', 'email', 'provider']), [
        'provider_id' => $userSocialAccount->getId()
      ]));
    }

    config('jwt')['required_claims'] = [];

    $token = JWTAuth::claims([
      'pending_user_id' => $pendingUser->id,
      'provider'        => $request->get('provider'),
    ])->fromUser(new User);

    return other(AuthResponses::DATA_NOT_COMPLETED, [
      'token'       => $token,
      'first_name'  => $pendingUser->first_name,
      'last_name'   => $pendingUser->last_name,
      'email'       => $pendingUser->email,
    ]);
  }

  /**
   * Complete use data then verify his phone.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function completeData(Request $request): JsonResponse
  {
    if (User::wherePhone($request->get('phone'))->orWhere('email', $request->get('email'))->first())
      return other(AuthResponses::EMAIL_OR_PHONE_USED);

    $this->validate($request, User::validations()->create());

    $payload = JWTAuth::setToken(str_replace('Bearer ', "" , $request->header('Authorization')))->getPayload();

    if (!isset($payload['pending_user_id']) || !($pendingUser = PendingUser::find($payload['pending_user_id'])))
      return other(401);

    DB::beginTransaction();
    try {

      $user = User::create($request->only([
        'first_name',
        'last_name',
        'email',
        'phone',
        'country_id',
      ]));

      $user->social()->create(["{$payload['provider']}_id" => $pendingUser->provider_id]);

      $pendingUser->delete();

      $user->configureUserAccount();

      $user->refresh();

      $user->configureFirestore();

      DB::commit();
    } catch (\Exception $e) {

      DB::rollBack();

      return failed();
    }

    DB::beginTransaction();
    try {

      $this->sendVerifyCodeBySMS($user->email, $user->getFullPhoneNumber());

      DB::commit();

    } catch (\Exception $e) {

      DB::rollBack();

      return other(TwilioCodes::SERVICE_NOT_AVAILABLE, [
        $e->getMessage()
      ]);
    }

    $token = JWTAuth ::fromUser($user);

    return other(AuthResponses::PHONE_VERIFICATION_REQUIRED, [
      'token'     => $token,
      'document'  => $user->firestore_ref,
      'phone'     => $user->getFullPhoneNumber(),
      'remaining' => 3 - $user->phone_verification_attempts
    ]);
  }

  /**
   * Register user then verify his phone.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function register(Request $request): JsonResponse
  {
    if (User::wherePhone($request->get('phone'))->orWhere('email', $request->get('email'))->first())
      return other(AuthResponses::EMAIL_OR_PHONE_USED);

    $this->validate($request, User::validations()->create());

    DB::beginTransaction();
    try {

      $user = User::create($request->only([
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'country_id',
      ]));

      $user->configureUserAccount();

      $user->refresh();

      $user->configureFirestore();

      DB::commit();
    } catch (\Exception $e) {

      DB::rollBack();

      return failed([
        $e->getMessage(),
      ]);
    }

    DB::beginTransaction();
    try {

      $this->sendVerifyCodeBySMS($user->email, $user->getFullPhoneNumber());

      DB::commit();

    } catch (\Exception $e) {

      DB::rollBack();

      return other(TwilioCodes::SERVICE_NOT_AVAILABLE);
    }

    $token = JWTAuth ::fromUser($user);

    return other(AuthResponses::PHONE_VERIFICATION_REQUIRED, [
      'token'     => $token,
      'document'  => $user->firestore_ref,
      'phone'     => $user->getFullPhoneNumber(),
      'remaining' => 3 - $user->phone_verification_attempts
    ]);
  }

  /**
   * Resend verification code to a user.
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function resendVerificationCode(Request $request): JsonResponse // TODO: rate limiting
  {
    $user = Auth::guard('user')->user();

    if ($user->is_phone_verified)
      return other(AuthResponses::ALREADY_VERIFIED);

    if ($user->phone_verification_attempts >= 3)
      return other(AuthResponses::MAX_ATTEMPTS_REACHED);

    DB::beginTransaction();
    try {

      $this->sendVerifyCodeBySMS($user->email, $user->getFullPhoneNumber());

      $user->phoneVerificationAttempts()->create([
        'phone' => $user->phone,
      ]);

      $user->increment('phone_verification_attempts');

      DB::commit();

      return success();

    } catch (\Exception $e) {

      DB::rollBack();

      return other(TwilioCodes::SERVICE_NOT_AVAILABLE, [$e->getMessage()]);
    }
  }

  /**
   * Check phone verification code.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function checkVerificationCode(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->checkVerification());

    $user = Auth::guard('user')->user();

    if ($user->is_phone_verified)
      return other(AuthResponses::ALREADY_VERIFIED);

    try {

      $verification = $this->checkVerifyCode($request->get('code'), $user->email, $user->getFullPhoneNumber());

    } catch (TwilioException $e) {

      return other(TwilioCodes::INVALID_CODE, [
        'reason' => $e->getMessage()
      ]);
    }

    if ($verification) {

      $user->makeVerified();

      try {

        $email = EmailTemplate::whereTitle('ACCOUNT_ACTIVATED_'.strtoupper($user->language->shortcut))->first() ?? EmailTemplate::whereTitle('ACCOUNT_ACTIVATED_DE')->first();

        if ($email)
          Mail::to($user)->send(new GeneralEmail(str_replace(['##first_name', '##app_link'], [$user->first_name, env('PLAYSTORE_URL')], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      $token = JWTAuth ::fromUser($user);

      return success([
        'token'    => $token,
        'document' => $user->firestore_ref,
        'expiry'   => (int)Auth ::guard('user') -> factory() -> getTTL(),
      ]);
    }

    return other(AuthResponses::INVALID_CODE);
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

    $code = UserPasswordReset::where(['user_id' => Auth::guard('user')->id(), 'status' => 'pending'])->first();

    if (! $code)
      return other(AuthResponses::INVALID_CREDENTIALS);

    DB::beginTransaction();
    try {

      $code->user->update(['password' => $request->get('password')]);

      $code->delete();

      $email = EmailTemplate::whereTitle('PASSWORD_CHANGED_'.strtoupper($code->user->language->shortcut))->first() ?? EmailTemplate::whereTitle('PASSWORD_CHANGED_DE')->first();

      if ($email)
        Mail::to($code->user)->send(new GeneralEmail(str_replace(['##first_name'], [$code->user->first_name], $email->template), $email->subject));

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

    $code = UserPasswordReset::where([
      'code'   => $request->get('code'),
      'status' => 'pending',
      'email'  => $request->get('email')])->first();

    if (! $code)
      return other(AuthResponses::INVALID_CODE);

    $token = JWTAuth ::fromUser($code->user);

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

    $user = User::where(['email' => $request->get('email')])->first();

    if (!$user)
      return other(AuthResponses::INVALID_CREDENTIALS);

    DB::beginTransaction();
    try {

      $user->passwordResets()->pending()->delete();

      $code = generateRandomCodeNumber(4);

      $user->passwordResets()->create([
        'code'  => $code,
        'email' => $user->email
      ]);

      try {

        $email = EmailTemplate::whereTitle('RESET_PASSWORD_'.strtoupper($user->language->shortcut))->first() ?? EmailTemplate::whereTitle('RESET_PASSWORD_DE')->first();

        if ($email)
          Mail::to($user)->send(new GeneralEmail(str_replace(['##first_name', '##code'], [$user->first_name, $code], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      DB::commit();

      return success();

    } catch (\Exception $e) {

      DB::rollBack();

      return failed([$e->getMessage(), $e->getTraceAsString()]);
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
      'expiry' => (int)Auth ::guard('user') -> factory() -> getTTL()
    ]);
  }
}
