<?php

namespace App\Modules\Settings\Controllers\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Settings\Models\DriverDocument;
use App\Modules\Settings\ApiPresenters\DriverDocumentPresenter;

class DriverDocumentController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var DriverDocument
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'documents';

  /**
   * DriverDocumentController constructor.
   */
  public function __construct ()
  {
    $this -> model = new DriverDocument();
  }

  /**
   * Show all models rows.
   */
  public function index ()
  {
    return success([
                     'rows' => DriverDocument ::orderBy('created_at', 'desc')->get()->map(function ($item) {
                       return [
                         'id'   => $item->id,
                         'field_name'=> $item->field_name,
                         'is_required' => $item->is_required,
                       ];
                     })
                   ]);
  }

  /**
   * Fetch Single DriverDocument Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'document' => (new DriverDocumentPresenter()) -> item($model)
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

    if ($this->exists(['field_name' => $request->get('field_name')], true))
      return other(911);

    DB ::beginTransaction();
    try {
      $document = $this -> model -> create($request -> only([
                                                            'field_name',
                                                            'is_required',
                                                          ]));

      DB ::commit();
    } catch (\Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $document -> id
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

    $document = $this -> shouldExists('id', $id);

    if ($this->exists(['field_name' => $request->get('field_name')], true, $id))
      return other(911);

    DB ::beginTransaction();
    try {
      $document -> update($request -> only([
                                         'field_name',
                                         'is_required',
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

  public function destroy(int $id): JsonResponse
  {
    $model = $this->shouldExists('id', $id);

    DB::beginTransaction();
    try {

      $model->delete();

      DB::commit();
    } catch (\Exception $e) {

      DB::rollBack();

      return failed();
    }

    return success();
  }

}
