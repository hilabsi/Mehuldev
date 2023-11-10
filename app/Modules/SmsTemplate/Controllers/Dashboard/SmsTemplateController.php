<?php

namespace App\Modules\SmsTemplate\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\SmsTemplate\Models\SmsTemplate;
use App\Modules\SmsTemplate\Enums\SmsTemplateResponses;
use App\Modules\SmsTemplate\ApiPresenters\SmsTemplatePresenter;

class SmsTemplateController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var SmsTemplate
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'smsTemplates';

  /**
   * SmsTemplateController constructor.
   */
  public function __construct ()
  {
    $this -> model = new SmsTemplate();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (): JsonResponse
  {
    return success([
                     'rows' => SmsTemplate ::all() -> map(function ($item) {
                       return (new SmsTemplatePresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single SmsTemplate Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show ($id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'template' => (new SmsTemplatePresenter()) -> item($model)
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
      $smsTemplate = $this -> model -> create($request -> only([
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
                     'id' => $smsTemplate -> id
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

    $template = $this -> shouldExists('id', $id);

    if (!$request -> hasAny([
                              'title',
                              'template',
                            ])) {
      return other(SmsTemplateResponses::NO_FIELDS_SENT);
    }

    DB ::beginTransaction();
    try {
      $template -> update($request -> only([
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
}
