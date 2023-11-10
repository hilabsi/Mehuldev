<?php

namespace App\Modules\EmailTemplate\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\EmailTemplate\Models\EmailTemplate;
use App\Modules\EmailTemplate\Enums\EmailTemplateResponses;
use App\Modules\EmailTemplate\ApiPresenters\EmailTemplatePresenter;

class EmailTemplateController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var EmailTemplate
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'emailTemplates';

  /**
   * EmailTemplateController constructor.
   */
  public function __construct ()
  {
    $this -> model = new EmailTemplate();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (): JsonResponse
  {
    return success([
                     'rows' => EmailTemplate ::all() -> map(function ($item) {
                       return (new EmailTemplatePresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single EmailTemplate Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show ($id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'template' => (new EmailTemplatePresenter()) -> item($model)
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
      $emailTemplate = $this -> model -> create($request -> only([
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
                     'id' => $emailTemplate -> id
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
      return other(EmailTemplateResponses::NO_FIELDS_SENT);
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
