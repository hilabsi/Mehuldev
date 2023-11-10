<?php

namespace App\Modules\CustomPage\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Modules\CustomPage\Models\CustomPage;
use App\Support\Traits\ModelManipulations;
use App\Modules\CustomPage\Enums\CustomPageResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\CustomPage\ApiPresenters\CustomPagePresenter;

class CustomPageController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var CustomPage
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
    $this -> model = new CustomPage();
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
                     'rows' => CustomPage ::all() -> map(function ($item) {
                       return (new CustomPagePresenter()) -> format($item);
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
                     'page' => (new CustomPagePresenter()) -> item($model)
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
  public function update ($id, Request $request): JsonResponse
  {
    $this -> validate($request, $this -> model ::validations() -> edit($id));

    $user = $this -> shouldExists('id', $id);

    DB ::beginTransaction();
    try {
      $user -> update($request -> only([
                                         'content',
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
