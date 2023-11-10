<?php

namespace App\Modules\Invoice\Controllers\Portal;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Invoice\Models\Invoice;
use App\Support\Traits\ModelManipulations;
use App\Modules\Invoice\Enums\InvoiceResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\Invoice\ApiPresenters\InvoicePresenter;

class InvoiceController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Invoice
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'invoices';

  /**
   * InvoiceController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Invoice();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (): JsonResponse
  {
    $partner = auth()->guard('partner')->user();
    return success([
                     'rows' => Invoice ::wherePartnerId($partner->id)->get() -> map(function ($item) {
                       return (new InvoicePresenter()) -> item($item);
                     })
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

      $invoice = $this
        -> model
        -> create($request -> only([
                                     'name',
                                     'email',
                                     'password',
                                     'role_id'
                                   ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $invoice -> id
                   ]);
  }

  /**
   * Fetch All Invoice Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $invoice = $this -> shouldExists('id', $id);

    return success([
                     'invoice' => (new InvoicePresenter()) -> item($invoice)
                   ]);
  }
}
