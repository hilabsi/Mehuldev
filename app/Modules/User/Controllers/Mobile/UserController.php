<?php

namespace App\Modules\User\Controllers\Mobile;

use Exception;
use Illuminate\Http\Request;
use Google\Cloud\Core\GeoPoint;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\User\Enums\AuthResponses;
use App\Modules\User\Enums\UserResponses;
use App\Support\Traits\ModelManipulations;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var User
   */
  protected User $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected string $type = 'users';

  /**
   * UserController constructor.
   */
  public function __construct()
  {
    $this->model = new User();
  }

  /**
   * Update device id.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function updateDeviceId(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->updateDeviceId());

    $user = auth()->guard('user')->user();

    DB::beginTransaction();
    try {

      $user->update($request->only(['device_id']));

      DB::commit();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }

    return success();
  }

  /**
   * Change user's language.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function changeLanguage(Request $request)
  {
    $this->validate($request, $this->model::validations()->updateLanguage());

    $user = auth()->guard('user')->user();

    DB::beginTransaction();
    try {

      $user->update($request->only(['language_id']));

      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed([
                      $exception->getCode(),
                      $exception->getMessage()
                    ]);
    }
  }

  /**
   * Update user business profile data.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function updateBusinessProfile(Request $request)
  {
    $this->validate($request, $this->model::validations()->updateBusinessProfile());

    $user = auth()->guard('user')->user();

    DB::beginTransaction();
    try {

      $user->updateBusiness($request->only(['company_name', 'company_address', 'email', 'uid']));

      $user->refresh();

      $user->updateFirestore([
                               ['path' => 'has_business', 'value' => 1],
                               ['path' => 'business_profile' , 'value' => [
                                 'company_address' => $user->business->company_address,
                                 'email' => $user->business->email,
                                 'uid' => $user->business->uid,
                                 'company_name' => $user->business->company_name,
                               ]],
                             ]);
      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed([
                      $exception->getCode(),
                      $exception->getMessage()
                    ]);
    }
  }

  /**
   * Update a place information.
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function updatePlace(Request $request)
  {
    $user = auth()->guard('user')->user();

    $this->validate($request, $this->model::validations()->updatePlace($user->id));

    DB::beginTransaction();
    try {
      $place = $user->places()->find($request->get('place_id'));

      $place->update($request->only(['location', 'address']));

      $user->updateFirestore([
                               ['path' => 'places', 'value' => $user->places->map(function ($place) {
                                 $l = $place->location;
                                 return [
                                   'id'        => $place->id,
                                   'address'   => $place->address,
                                   'type'      => $place->type,
                                   'location'  => $l ? ['U' => $l->getLat(), 'k' => $l->getLng()] : null,
                                 ];
                               })->toArray()]
                             ]);

      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }
  }

  /**
   * Change user's password
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function changePassword(Request $request)
  {
    $this->validate($request, $this->model::validations()->changePassword());

    $user = auth()->guard('user')->user();

    if ($hasPassword = !!$user->password) {
      if (!app('hash')->check($request->get('old_password'), $user->password)) {
        return other(UserResponses::PASSWORD_NOT_MATCHED);
      }
    }


    DB::beginTransaction();
    try {

      $user->update(['password' => $request->get('new_password')]);

      if (!$hasPassword)
        $user->updateFirestore([
                                 ['path' => 'has_password', 'value' => true]
                               ]);

      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }

  }

  /**
   * Change user's password
   *
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update(Request $request)
  {
    $this->validate($request, $this->model::validations()->updateProfile());

    $user = auth()->guard('user')->user();

    if ($this->model->where('id', '!=', $user->id)->where(['email' => $request->get('email')])->first()) {
      return other(AuthResponses::EMAIL_OR_PHONE_USED);
    }

    DB::beginTransaction();
    try {

      $user->update($request->only([
                                     'first_name',
                                     'last_name',
                                     'email',
                                   ]));

      if($request->file('picture')) {

        $user->update($request->only(['picture']));

        $user->refresh();

        $user->updateFirestore([
                                 ['path' => 'picture'    , 'value' => $user->picture],
                               ]);
      }

      $user->updateFirestore([
                               ['path' => 'first_name' , 'value' => $user->first_name],
                               ['path' => 'last_name'  , 'value' => $user->last_name],
                               ['path' => 'email'      , 'value' => $user->email],
                             ]);

      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }

  }

  public function updateLocation(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->updateLocation());

    $user = auth()->guard('user')->user();

    DB::beginTransaction();
    try {

      $user->update(['location' => new Point($request->get('location')['lat'], $request->get('location')['lng']), 4326]);

      $user->updateFirestore([
                               ['path' => 'current_location', 'value' => $user->location]
                             ]);

      DB::commit();

    } catch (\Exception $exception) {
      DB::rollBack();

      return failed();
    }

    return success();
  }
}
