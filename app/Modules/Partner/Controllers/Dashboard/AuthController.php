<?php

namespace App\Modules\Partner\Controllers\Dashboard;

use App\Modules\EmailTemplate\Models\EmailTemplate;
use App\Modules\Partner\Enums\PartnerResponses;
use App\Modules\Partner\Mails\GeneralEmail;
use App\Modules\Partner\Mails\RegistrationMail;
use App\Support\Traits\ModelManipulations;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Modules\Partner\Models\Partner;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Modules\Partner\Enums\AuthResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\Partner\ApiPresenters\PartnerPresenter;

class AuthController extends Controller
{
  use ModelManipulations;

  /**
   * @var Partner
   */
  protected Partner $model;

  /**
   * AuthController constructor.
   */
  public function __construct()
  {
    $this->model = new Partner;
  }

  /**
   * Generate an access token for partner by email.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function loginByEmail (Request $request) : JsonResponse
  {
//    $this -> validate($request, $this -> model ::validations() -> loginByEmail());

    try {

      if ($token = auth('partner')->attempt($request -> only(['email', 'password']))) {

        $partner = Auth ::guard('partner')->user();

        if ($partner->status !== 'active')
          return other(AuthResponses::INVALID_CREDENTIALS);

        $params = [
          'token'   => $token,
          'profile' => (new PartnerPresenter())->profile($partner),
          'expiry'  => (int)Auth ::guard('partner') -> factory() -> getTTL(),
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

      return other(AuthResponses::INVALID_CREDENTIALS);
    }

    return success([
      'token'  => $token,
      'expiry' => (int)Auth ::guard('partner') -> factory() -> getTTL()
    ]);
  }

  public function register(Request $request): JsonResponse
  {
    $this->model = new Partner();
    $this -> validate($request, $this -> model ::validations() -> create());

    if ($this->exists(['email' => $request->get('email')], true)) {
      return other(PartnerResponses::USED_EMAIL);
    }

    if ($this->exists(['phone' => $request->get('phone')], true)) {
      return other(PartnerResponses::USED_PHONE);
    }

    DB ::beginTransaction();
    try {

      $partner = $this
        -> model
        -> create($request -> only([
          'company_name',
          'first_name',
          'last_name',
          'country_id',
          'city_id',
          'email',
          'phone',
          'language_id',
          'percent',
          'billing_type',
          'address',
          'password',
          'fna',
          'uid',
          'account_owner',
          'iban',
          'bic',
        ]));

      $partner->setDocuments($request->allFiles());

      $partner->configureFirestore();

      $code = createToken(10);

      $partner->update(['verification_code' => $code]);

      try {

        $email = EmailTemplate::whereTitle('PARTNER_REGISTERED_'.strtoupper($partner->language->shortcut))->first() ?? EmailTemplate::whereTitle('PARTNER_REGISTERED_DE')->first();

        if ($email)
          Mail::to($partner)->send(new GeneralEmail(str_replace(['##first_name', '##portal_url'], [$partner->first_name, 'https://portal.lobi.at/verify/'.$code], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      DB ::commit();

    } catch (\Exception $exception) {

      DB ::rollBack();

      return failed([
        $exception -> getMessage()
      ]);
    }

    return success();
  }

  public function verify(Request $request): JsonResponse
  {
    if ($partner = Partner::where('is_email_verified', 0)->whereVerificationCode($request->get('code'))->first()) {

      $partner->update(['is_email_verified' => 1]);

      return success();
    } else return other(404);
  }
}
