<?php

namespace App\Modules\Settings\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Modules\Settings\ApiPresenters\CarModelPresenter;
use App\Modules\Settings\Models\CarModel;
use App\Support\Traits\ModelManipulations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarModelController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var CarModel
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'settings';

  /**
   * CarModelController constructor.
   */
  public function __construct ()
  {
    $this -> model = new CarModel();
  }

  /**
   * Show all models rows.
   */
  public function index ()
  {
    return success([
                     'rows' => CarModel ::all()->map(function ($item) {
                       return [
                         'id'       => $item->id,
                         'brand_id' => $item->brand_id,
                         'brand'     => [
                           'id' => $item -> brand->id,
                           'title' => $item -> brand->title,
                         ],
                         'title'    => $item->title,
                         'code'     => $item->code,
                       ];
                     })
                   ]);
  }

  /**
   * Fetch Single CarModel Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'model' => (new CarModelPresenter()) -> item($model)
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

    if ($this->model->whereBrandId($request->get('brand_id'))->whereTitle($request->get('title'))->first())
      return other(911);

    DB ::beginTransaction();
    try {
      $model = $this -> model -> create($request -> only([
                                                           'brand_id',
                                                           'title',
                                                           'code'
                                                          ]));

      DB ::commit();
    } catch (\Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $model -> id
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

    $model = $this -> shouldExists('id', $id);

    if ($this->model->where('id', '!=', $id)->whereBrandId($request->get('brand_id'))->whereTitle($request->get('title'))->first())
      return other(911);

    DB ::beginTransaction();
    try {
      $model -> update($request -> only([
                                         'brand_id',
                                         'title',
                                         'code'
                                       ]));

      DB ::commit();
    } catch (\Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getCode(),
                      $exception -> getMessage()
                    ]);
    }

    return success();
  }
}
