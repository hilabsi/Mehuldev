<?php

namespace App\Modules\Support\Controllers\Dashboard;

use App\Modules\Support\Models\SupportCategoryQuestion;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Support\Models\SupportCategory;
use App\Modules\Support\ApiPresenters\SupportCategoryPresenter;

class SupportCategoryController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var SupportCategory
   */
  protected SupportCategory $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'categories';

  /**
   * SupportCategoryController constructor.
   */
  public function __construct ()
  {
    $this -> model = new SupportCategory();
  }

  /**
   * Show all models rows.
   */
  public function index(): JsonResponse
  {
    return success([
                     'rows' => SupportCategory ::orderBy('created_at', 'desc')->get() -> map(function ($item) {
                       return (new SupportCategoryPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single SupportCategory Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'category' => (new SupportCategoryPresenter()) -> item($model)
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
      $category = $this -> model -> create($request -> only([
                                                              'name',
                                                              'type',
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
                     'id' => $category -> id
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
                                         'name',
                                         'type',
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

  public function destroy(int $id): JsonResponse
  {
    $model = $this->shouldExists('id', $id);

    DB::beginTransaction();
    try {

      SupportCategoryQuestion::whereCategoryId($id)->delete();

      $model->delete();

      DB::commit();
    } catch (\Exception $e) {

      DB::rollBack();

      return failed();
    }

    return success();
  }
}
