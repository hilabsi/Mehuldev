<?php

namespace App\Modules\Admin\Controllers\Dashboard;

use App\Modules\Admin\ApiPresenters\AdminPresenter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Modules\Admin\Models\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Modules\Admin\Enums\AuthResponses;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
  /**
   * @var Admin
   */
  protected Admin $model;

  /**
   * AuthController constructor.
   */
  public function __construct()
  {
    $this->model = new Admin;
  }

  /**
   * Generate an access token for admin by email.
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

      if ($token = auth('admin')->attempt($request -> only(['email', 'password']))) {

        $admin = Auth ::guard('admin')->user();

        if ($admin->isDisabled())
          return other(AuthResponses::INVALID_CREDENTIALS);

        $params = [
          'token'   => $token,
          'profile' => (new AdminPresenter())->profile($admin),
          'expiry'  => (int)Auth ::guard('admin') -> factory() -> getTTL(),
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
                     'expiry' => (int)Auth ::guard('admin') -> factory() -> getTTL()
                   ]);
  }
}
