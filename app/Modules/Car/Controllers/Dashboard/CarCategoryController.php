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
use App\Modules\Car\ApiPresenters\CarCategoryPresenter;

class CarCategoryController extends Controller
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
   * CarCategoryController constructor.
   */
  public function __construct ()
  {
    $this -> model = new CarCategory();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    if ($request->get('enabled'))
      return success([
                       'rows' => CarCategory ::whereStatus('enabled')->orderBy('created_at', 'desc')->get() -> map(function ($item) {
                         return (new CarCategoryPresenter()) -> item($item);
                       })
                     ]);
    return success([
                     'rows' => CarCategory ::orderBy('created_at', 'desc')->get() -> map(function ($item) {
                       return (new CarCategoryPresenter()) -> item($item);
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

    if ($this->exists(['name' => $request->get('name')], true)) {
      return other(CarCategoryResponses::USED_NAME);
    }

    DB ::beginTransaction();
    try {

      $category = $this -> model -> create($request -> only([
                                                              'name',
                                                              'seats',
                                                              'start_price',
                                                              'km_price',
                                                              'minimum_price',
                                                              'minute_price',
                                                              'range_percent',
                                                              'free_wait_time',
                                                              'wait_minute_price',
                                                              'status',
                                                              'type',
                                                            ]));

      $category->update(['image' => $request->file('image')]);

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
                     'category' => (new CarCategoryPresenter()) -> item($category)
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

    $this -> validate($request, $this -> model ::validations() -> edit($category->id));

    if ($this->exists(['name' => $request->get('name')], true, $id)) {
      return other(CarCategoryResponses::USED_NAME);
    }

    if (!$request -> hasAny([
                              'name',
                              'seats',
                              'start_price',
                              'km_price',
                              'minimum_price',
                              'minute_price',
                              'range_percent',
                              'image',
                              'free_wait_time',
                              'wait_minute_price',
                              'status',
                              'type',
                            ])) {
      return other(CarCategoryResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {

      $category -> update($request -> only([
                                             'name',
                                             'seats',
                                             'start_price',
                                             'km_price',
                                             'minimum_price',
                                             'minute_price',
                                             'range_percent',
                                             'image',
                                             'free_wait_time',
                                             'wait_minute_price',
                                             'status',
                                             'type',
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
