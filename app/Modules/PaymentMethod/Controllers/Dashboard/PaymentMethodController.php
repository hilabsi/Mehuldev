<?php

namespace App\Modules\PaymentMethod\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\PaymentMethod\Models\PaymentMethod;
use App\Modules\PaymentMethod\Enums\PaymentMethodResponses;
use App\Modules\PaymentMethod\ApiPresenters\PaymentMethodPresenter;

class PaymentMethodController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var PaymentMethod
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'payment-methods';

  /**
   * PaymentMethodController constructor.
   */
  public function __construct ()
  {
    $this -> model = new PaymentMethod();
  }

  /**
   * Show all models rows.
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    return success([
                     'rows' => $request -> get('enabled') ? PaymentMethod ::enabled() -> get() -> map(function ($item) {
                       return (new PaymentMethodPresenter()) -> item($item);
                     }) : PaymentMethod ::all() -> map(function ($item) {
                       return (new PaymentMethodPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single PaymentMethod Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'method' => (new PaymentMethodPresenter()) -> item($model)
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
      $method = $this -> model -> create($request -> only([
                                                            'name',
                                                          ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $method -> id
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

    if (!$request -> hasAny([
                              'name',
                              'status'
                            ])) {
      return other(PaymentMethodResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {
      $user -> update($request -> only([
                                         'name',
                                         'status'
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
