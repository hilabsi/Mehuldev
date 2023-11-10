<?php

namespace App\Modules\Settings\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Modules\Settings\Models\CarBrand;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Settings\ApiPresenters\CarBrandPresenter;

class CarBrandController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var CarBrand
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'brands';

  /**
   * CarBrandController constructor.
   */
  public function __construct ()
  {
    $this -> model = new CarBrand();
  }

  /**
   * Show all models rows.
   */
  public function index ()
  {
    return success([
                     'rows' => CarBrand ::orderBy('created_at', 'desc')->get()->map(function ($item) {
                       return [
                         'id'   => $item->id,
                         'title'=> $item->title,
                         'code' => $item->code,
                       ];
                     })
                   ]);
  }

  /**
   * Fetch Single CarBrand Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'brand' => (new CarBrandPresenter()) -> item($model)
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
                                                            'title',
                                                            'code',
                                                          ]));

      DB ::commit();
    } catch (\Exception $exception) {
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
                                         'title',
                                         'code',
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
