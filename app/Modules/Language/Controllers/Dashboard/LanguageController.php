<?php

namespace App\Modules\Language\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Modules\Language\Models\Language;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Language\Enums\LanguageStatus;
use App\Modules\Language\Enums\LanguageResponses;
use App\Support\Exceptions\InvalidEnumerationException;
use App\Modules\Language\ApiPresenters\LanguagePresenter;

class LanguageController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var Language
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'languages';

  /**
   * LanguageController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Language();
  }

  /**
   * Show all models rows.
   */
  public function index (Request $request)
  {
    return success([
                     'rows' => $request -> get('enabled') ? Language ::enabled() -> get() -> map(function ($item) {
                       return (new LanguagePresenter()) -> item($item);
                     }) : Language ::all() -> map(function ($item) {
                       return (new LanguagePresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single Language Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'language' => (new LanguagePresenter()) -> item($model)
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

    if ($request -> get('status')) {
      try {
        LanguageStatus ::includes($request -> get('status'));
      } catch (InvalidEnumerationException $exception) {
        return other(LanguageResponses::STATUS_OUT_OF_BOUND);
      }
    }

    DB ::beginTransaction();
    try {
      $language = $this -> model -> create($request -> only([
                                                              'name',
                                                              'status',
                                                              'shortcut'
                                                            ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $language -> id
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

    if (!$request -> hasAny([
                              'status'
                            ])) {
      return other(LanguageResponses::NO_FIELDS_SENT);
    }

    if ($request -> has('status')) {
      try {
        LanguageStatus ::includes($request -> get('status'));
      } catch (InvalidEnumerationException $exception) {
        return other(LanguageResponses::STATUS_OUT_OF_BOUND);
      }
    }

    DB ::beginTransaction();
    try {
      $user -> update($request -> only([
                                         'status'
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
