<?php

namespace App\Modules\Support\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Trip\Models\TripMissingRequest;
use App\Modules\Support\ApiPresenters\MissingRequestPresenter;

class MissingRequestController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var TripMissingRequest
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'missing-requests';

  /**
   * TripMissingRequestController constructor.
   */
  public function __construct ()
  {
    $this -> model = new TripMissingRequest();
  }

  /**
   * Show all models rows.
   * @return JsonResponse
   */
  public function index()
  {
    return success([
                     'rows' => TripMissingRequest::all() -> map(function ($item) {
                       return (new MissingRequestPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single Request Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'request' => (new MissingRequestPresenter()) -> item($model)
                   ]);
  }

  /**
   * Update model data.
   *
   * @param Int     $id
   * @param Request $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update (int $id, Request $request): JsonResponse
  {
    $this -> validate($request, [
      'status' => 'required|in:pending,reviewed,closed',
    ]);

    $user = $this -> shouldExists('id', $id);

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
