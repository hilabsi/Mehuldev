<?php

namespace App\Modules\Admin\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Modules\Admin\Models\Admin;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\AdminView;
use App\Support\Traits\ModelManipulations;
use App\Modules\Admin\Enums\AdminResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\Admin\ApiPresenters\AdminPresenter;

class AdminController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Admin
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'admins';

  /**
   * AdminController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Admin();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (): JsonResponse
  {
    return success([
                     'rows' => Admin ::orderBy('created_at', 'desc')->get() -> map(function ($item) {
                       return (new AdminPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Show all models rows.
   *
   * @param Request $request
   *
   * @return JsonResponse
   */
  public function search (Request $request): JsonResponse
  {
    $availableFilters = [
      'id',
      'name',
      'email',
      'role_id',
      'is_active'
    ];

    if (!$request -> hasAny($availableFilters)) {
      return other(AdminResponses::FILTER_NOT_AVAILABLE);
    }

    return success([
                     'rows' => AdminView ::where($request -> only($availableFilters)) -> get() -> map(function ($item) {
                       return (new AdminPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Save model data.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function store (Request $request): JsonResponse
  {
    $this -> validate($request, $this -> model ::validations() -> create());

    if ($this->exists(['email' => $request->get('email')], true)) {
      return other(AdminResponses::USED_EMAIL);
    }

    DB ::beginTransaction();
    try {

      $admin = $this
        -> model
        -> create($request -> only([
                                     'name',
                                     'email',
                                     'password',
                                     'role_id',
                                     'country_id'
                                   ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $admin -> id
                   ]);
  }

  /**
   * Fetch All Admin Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $admin = $this -> shouldExists('id', $id);

    return success([
                     'admin' => (new AdminPresenter()) -> item($admin)
                   ]);
  }

  /**
   * Update model data.
   *
   * @param String  $id
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update (string $id, Request $request): JsonResponse
  {
    $admin = $this -> shouldExists('id', $id);

    $this -> validate($request, $this -> model ::validations() -> edit($id));

    if ($this->exists(['email' => $request->get('email')], true, $id)) {
      return other(AdminResponses::USED_EMAIL);
    }

    if (!$request -> hasAny([
                              'name',
                              'email',
                              'role_id',
                              'country_id',
                              'password'
                            ])) {
      return other(AdminResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {
      $admin -> update($request -> only([
                                          'name',
                                          'email',
                                          'password',
                                          'role_id',
                                          'country_id',
                                        ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getCode(),
                      $exception -> getMessage()
                    ]);
    }

    return success();
  }

  /**
   * Change admin status to disabled
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function disable (string $id): JsonResponse
  {
    $admin = $this -> shouldExists('id', $id);

    if ($admin->isDisabled()) {
      return other(AdminResponses::ACCOUNT_DISABLED);
    }

    DB ::beginTransaction();
    try {

      $admin -> disable();

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getCode(),
                      $exception -> getMessage()
                    ]);
    }

    return success();
  }

  /**
   * Change admin status to active
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function activate (string $id): JsonResponse
  {
    $admin = $this -> shouldExists('id', $id);

    if ($admin->isActive()) {
      return other(AdminResponses::ACCOUNT_ALREADY_ACTIVATED);
    }

    DB ::beginTransaction();
    try {

      $admin -> activate();

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getCode(),
                      $exception -> getMessage()
                    ]);
    }

    return success();
  }
}
