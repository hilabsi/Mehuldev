<?php

namespace App\Modules\Campaign\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use App\Modules\Campaign\Models\SMSTemplate;
use Illuminate\Validation\ValidationException;
use App\Modules\Campaign\Enums\CampaignResponses;
use App\Modules\Campaign\ApiPresenters\SMSTemplatePresenter;

class SMSTemplateController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var SMSTemplate
   */
  protected SMSTemplate $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'campaigns';

  /**
   * SMSTemplateController constructor.
   */
  public function __construct ()
  {
    $this -> model = new SMSTemplate();
  }

  /**
   * Show all models rows.
   */
  public function index(): JsonResponse
  {
    return success([
                     'rows' => SMSTemplate ::orderBy('created_at', 'desc')->get() -> map(function ($item) {
                       return (new SMSTemplatePresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single SMSTemplate Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'template' => (new SMSTemplatePresenter()) -> item($model)
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
      $template = $this -> model -> create($request -> only([
                                                              'title',
                                                              'template',
                                                            ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $template -> id
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

    $user = $this -> shouldExists('id', $id);

    if (!$request -> hasAny([
                              'title',
                              'template',
                            ])) {
      return other(CampaignResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {
      $user -> update($request -> only([
                                         'title',
                                         'template',
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

  /**
   * @param $id
   * @return JsonResponse
   */
  public function destroy($id): JsonResponse
  {
    $model = $this->shouldExists('id', $id);

    $model->delete();

    return success();
  }
}
