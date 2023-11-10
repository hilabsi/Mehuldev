<?php

namespace App\Modules\Coupon\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Modules\Coupon\Models\Coupon;
use App\Support\Traits\ModelManipulations;
use App\Modules\Coupon\Enums\CouponResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\Coupon\ApiPresenters\CouponPresenter;

class CouponController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var Coupon
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'coupon';

  /**
   * CustomPageController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Coupon();
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
                     'rows' => Coupon ::orderBy('created_at', 'desc')->get() -> map(function ($item) {
                       return (new CouponPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single CustomPage Information
   *
   * @param $id
   *
   * @return JsonResponse
   */
  public function show ($id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'coupon' => (new CouponPresenter()) -> item($model)
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

    if ($this->model->whereCode($request->get('code'))->first()) {
      return other(CouponResponses::COUPON_ALREADY_ADDED);
    }

    DB ::beginTransaction();
    try {
      $coupon = $this -> model -> create($request -> only([
                                                            'name',
                                                            'expiring_at',
                                                            'description',
                                                            'amount',
                                                            'quantity',
                                                            'amount_type',
                                                            'code',
                                                            'max_usage_per_user',
                                                          ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $coupon -> id
                   ]);
  }

  /**
   * Update model data.
   *
   * @param $id
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update ($id, Request $request): JsonResponse
  {
    $this -> validate($request, $this -> model ::validations() -> edit($id));

    if ($this->model->whereCode($request->get('code'))->where('id', '!=', $id)->first()) {
      return other(CouponResponses::COUPON_ALREADY_ADDED);
    }

    $user = $this -> shouldExists('id', $id);

    if (!$request -> hasAny([
                              'name',
                              'expiring_at',
                              'description',
                              'amount',
                              'quantity',
                              'amount_type',
                              'code',
                              'max_usage_per_user',
                            ])) {
      return other(CouponResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {
      $user -> update($request -> only([
                                         'name',
                                         'expiring_at',
                                         'description',
                                         'amount',
                                         'quantity',
                                         'amount_type',
                                         'code',
                                         'max_usage_per_user',
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
