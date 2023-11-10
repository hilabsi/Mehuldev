<?php

namespace App\Modules\Partner\Controllers\Dashboard;

use App\Modules\EmailTemplate\Models\EmailTemplate;
use App\Modules\Invoice\ApiPresenters\InvoicePresenter;
use App\Modules\Invoice\Models\ClientInvoice;
use App\Modules\Partner\Mails\GeneralEmail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Partner\Models\Partner;
use App\Support\Traits\ModelManipulations;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use App\Modules\Partner\Enums\PartnerResponses;
use App\Modules\Partner\ApiPresenters\PartnerPresenter;

class PartnerController extends Controller
{
  use ModelManipulations;
  use Validations;

  /**
   *
   * @var Partner
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'partners';

  /**
   * PartnerController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Partner();
  }

  /**
   * Show all models rows.
   *
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    $country_id = $request->get('country_id');
    
    if ($request->get('enabled')) {
     if($country_id == 0)
     {
        return success([
            'rows' => Partner ::whereIsDeleted(0)->orderBy('created_at', 'desc') ->get()-> map(function ($item) {
              return (new PartnerPresenter()) -> item($item);
            })
          ]);
     }
     else
     {
        return success([
            'rows' => Partner ::whereIsDeleted(0)->where('country_id',$country_id)->orderBy('created_at', 'desc') ->get()-> map(function ($item) {
              return (new PartnerPresenter()) -> item($item);
            })
          ]); 
     }
      
    }
    
    if($country_id == 0)
    {
        return success([
          'rows' => Partner ::orderBy('created_at', 'desc')->get() -> map(function ($item) {
            return (new PartnerPresenter()) -> item($item);
          })
        ]);
    }
    else
    {
        return success([
          'rows' => Partner ::where('country_id',$country_id)->orderBy('created_at', 'desc')->get() -> map(function ($item) {
            return (new PartnerPresenter()) -> item($item);
          })
        ]);
    }
    
  }

  /**
   * Show all models rows.
   *
   * @param Request $request
   *
   * @return JsonResponse
   */
  public function search (Request $request): JsonResponse
  {
    $availableFilters = [
      'company_name',
      'first_name',
      'last_name',
      'country_id',
      'city_id',
      'email',
      'percent',
      'phone',
      'language_id',
      'billing_type',
      'address',
      'fna',
      'uid',
      'account_owner',
      'iban',
      'bic',
    ];

    if (!$request -> hasAny($availableFilters)) {
      return other(PartnerResponses::FILTER_NOT_AVAILABLE);
    }

    return success([
      'rows' => Partner ::where($request -> only($availableFilters)) -> get() -> map(function ($item) {
        return (new PartnerPresenter()) -> item($item);
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

    if ($this->exists(['email' => $request->get('email')], true)) {
      return other(PartnerResponses::USED_EMAIL);
    }

    if ($this->exists(['phone' => $request->get('phone')], true)) {
      return other(PartnerResponses::USED_PHONE);
    }

    DB ::beginTransaction();
    try {

      $partner = $this
        -> model
        -> create(array_merge($request -> only([
          'company_name',
          'first_name',
          'last_name',
          'country_id',
          'city_id',
          'email',
          'phone',
          'language_id',
          'billing_type',
          'address',
          'password',
          'fna',
          'uid',
          'account_owner',
          'iban',
          'bic',
        ]), [
          'percent' => settings('partner_percent')
        ]));

      $partner->setDocuments($request->allFiles());

      $partner->configureFirestore();

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
        $exception -> getMessage()
      ]);
    }

    return success([
      'id' => $partner -> id
    ]);
  }

  /**
   * Fetch All Partner Information
   *
   * @param String $id
   *
   * @return JsonResponse
   */
  public function show (string $id): JsonResponse
  {
    $partner = $this -> shouldExists('id', $id);

    return success([
      'partner' => (new PartnerPresenter()) -> item($partner)
    ]);
  }

  /**
   * Update model data.
   *
   * @param String  $id
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update (string $id, Request $request): JsonResponse
  {
    $partner = $this -> shouldExists('id', $id);

    $this -> validate($request, $this -> model ::validations() -> edit($id));

    if ($this->exists(['email' => $request->get('email')], true, $id)) {
      return other(PartnerResponses::USED_EMAIL);
    }

    if ($this->exists(['phone' => $request->get('phone')], true, $id)) {
      return other(PartnerResponses::USED_PHONE);
    }

    DB ::beginTransaction();
    try {
      $partner -> update($request -> only([
        'company_name',
        'first_name',
        'percent',
        'last_name',
        'country_id',
        'city_id',
        'password',
        'email',
        'phone',
        'language_id',
        'billing_type',
        'address',
        'fna',
        'uid',
        'account_owner',
        'iban',
        'bic',
      ]));

      $partner->setDocuments($request->allFiles());

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

  public function activate($id): JsonResponse
  {
    $partner = Partner::find($id);

    DB::beginTransaction();
    try {

      $partner->update(['status' => 'active']);

      try {

        $email = EmailTemplate::whereTitle('PARTNER_ACTIVATED_'.strtoupper($partner->language->shortcut))->first() ?? EmailTemplate::whereTitle('PARTNER_ACTIVATED_DE')->first();

        if ($email)
          Mail::to($partner)->send(new GeneralEmail(str_replace(['##first_name', '##portal_url'], [$partner->first_name, env('PORTAL_URL', 'ttps://portal.lobi.at/')], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed();
    }
  }

  public function invoices($id): JsonResponse
  {
    $partner = Partner::find($id);

    return success([
      'rows' => ClientInvoice::wherePartnerId($id)->get()->map(function ($item) {
        return (new InvoicePresenter())->item($item);
      })
    ]);
  }

  public function destroy($id): JsonResponse
  {
    $partner = Partner::find($id);

    DB::beginTransaction();
    try {

      $partner->update(['status' => 'suspended']);

      try {

        $email = EmailTemplate::whereTitle('PARTNER_DEACTIVATED_'.strtoupper($partner->language->shortcut))->first() ?? EmailTemplate::whereTitle('PARTNER_DEACTIVATED_DE')->first();

        if ($email)
          Mail::to($partner)->send(new GeneralEmail(str_replace(['##first_name', '##portal_url'], [$partner->first_name, env('PORTAL_URL', 'ttps://portal.lobi.at/')], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed();
    }
  }
}
