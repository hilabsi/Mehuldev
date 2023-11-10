<?php

namespace App\Modules\Car\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Car\Models\CarCategory;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Car\Enums\CarCategoryResponses;
use App\Modules\Car\Models\CarCategoryCityPricing;
use App\Modules\Car\ApiPresenters\CarCategoryCityPricingPresenter;

class CarCategoryCityPricingController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var CarCategory
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'car-categories';

  /**
   * CarCategoryCityPricingController constructor.
   */
  public function __construct ()
  {
    $this -> model = new CarCategoryCityPricing();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    if ($request->get('category_id'))
      return success([
                       'rows' => CarCategoryCityPricing ::whereCategoryId($request->get('category_id'))->orderBy('created_at', 'desc')->get() -> map(function ($item) {
                         return (new CarCategoryCityPricingPresenter()) -> item($item);
                       })
                     ]);
    return success([
                     'rows' => CarCategoryCityPricing ::orderBy('created_at', 'desc')->get() -> map(function ($item) {
                       return (new CarCategoryCityPricingPresenter()) -> item($item);
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

    if ($this->exists(['from_city' => $request->get('from_city'), 'to_city' => $request->get('to_city')], true))
      return other(CarCategoryResponses::ALREADY_ADDED);

    DB ::beginTransaction();
    try {

      $category = $this -> model -> create($request -> only([
                                                              'country_id',
                                                              'from_city',
                                                              'to_city',
                                                              'category_id',
                                                              'start_price',
                                                              'km_price',
                                                              'minimum_price',
                                                              'minute_price',
                                                              'range_percent',
                                                              'free_wait_time',
                                                              'wait_minute_price',
                                                            ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $category -> id
                   ]);
  }

  /**
   * Fetch All Car Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $category = $this -> shouldExists('id', $id);

    return success([
                     'pricing' => (new CarCategoryCityPricingPresenter()) -> item($category)
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
    $category = $this -> shouldExists('id', $id);

    if ($this->exists(['from_city' => $request->get('from_city'), 'to_city' => $request->get('to_city')], true, $id))
      return other(CarCategoryResponses::ALREADY_ADDED);

    $this -> validate($request, $this -> model ::validations() -> edit($category->id));

    if (!$request -> hasAny([
                              'country_id',
                              'from_city',
                              'to_city',
                              'start_price',
                              'km_price',
                              'minimum_price',
                              'minute_price',
                              'range_percent',
                              'free_wait_time',
                              'wait_minute_price',
                            ])) {
      return other(CarCategoryResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {

      $category -> update($request -> only([
                                             'country_id',
                                             'from_city',
                                             'to_city',
                                             'start_price',
                                             'km_price',
                                             'minimum_price',
                                             'minute_price',
                                             'range_percent',
                                             'free_wait_time',
                                             'wait_minute_price',
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

  public function destroy($id)
  {
    $category = $this -> shouldExists('id', $id);

    $category->delete();

    return success();
  }
}
