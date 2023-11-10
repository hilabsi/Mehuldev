<?php

namespace App\Modules\Support\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Support\Models\SupportCategoryQuestion;
use App\Modules\Support\ApiPresenters\SupportCategoryQuestionPresenter;

class SupportCategoryQuestionController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var SupportCategoryQuestion
   */
  protected SupportCategoryQuestion $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'questions';

  /**
   * SupportCategoryQuestionController constructor.
   */
  public function __construct ()
  {
    $this -> model = new SupportCategoryQuestion();
  }

  /**
   * Show all models rows.
   */
  public function index($id): JsonResponse
  {
    return success([
                     'rows' => SupportCategoryQuestion ::whereCategoryId($id)->orderBy('created_at', 'desc')->get() -> map(function ($item) {
                       return (new SupportCategoryQuestionPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single SupportCategoryQuestion Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'question' => (new SupportCategoryQuestionPresenter()) -> item($model)
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
      $question = $this -> model -> create($request -> only([
                                                              'name',
                                                              'type',
                                                              'category_id',
                                                              'enable_help',
                                                              'text',
                                                              'action',
                                                              'link',
                                                            ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $question -> id
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
                                         'enable_help',
                                         'text',
                                         'action',
                                         'link',
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

    $model->delete();

    return success();
  }
}
