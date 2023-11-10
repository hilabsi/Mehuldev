<?php

namespace App\Modules\Settings\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Settings\Models\UserCancelReason;
use App\Modules\Settings\ApiPresenters\UserCancelReasonPresenter;

class UserCancelReasonController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var UserCancelReason
   */
  protected UserCancelReason $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'reasons';

  /**
   * UserCancelReasonController constructor.
   */
  public function __construct ()
  {
    $this -> model = new UserCancelReason();
  }

  /**
   * Show all models rows.
   */
  public function index(): JsonResponse
  {
    return success([
                     'rows' => UserCancelReason ::orderBy('created_at', 'desc')->get()-> map(function ($item) {
                       return (new UserCancelReasonPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single UserCancelReason Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'category' => (new UserCancelReasonPresenter()) -> item($model)
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

    DB ::beginTransaction();
    try {
      $reason = $this -> model -> create($request -> only([
                                                            'reason',
                                                            'language_id',
                                                            ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $reason -> id
                   ]);
  }

  /**
   * Update model data.
   *
   * @param Int     $id
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update (int $id, Request $request): JsonResponse
  {
    $this -> validate($request, $this -> model ::validations() -> edit($id));

    $user = $this -> shouldExists('id', $id);

    DB ::beginTransaction();
    try {
      $user -> update($request -> only([
                                         'reason',
                                         'language_id',
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
}
