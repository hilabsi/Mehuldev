<?php

namespace App\Modules\Settings\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Modules\Settings\ApiPresenters\SettingsPresenter;
use App\Modules\Settings\Enums\SettingsResponses;
use App\Modules\Settings\Models\Settings;
use App\Support\Traits\ModelManipulations;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var Settings
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'settings';

  /**
   * SettingsController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Settings();
  }

  /**
   * Show all models rows.
   */
  public function index ()
  {
    return success([
                     'rows' => SettingsView ::all() -> map(function ($item) {
                       return (new SettingsPresenter()) -> item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single Settings Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show (int $id): JsonResponse
  {
    $model = $this -> shouldExists('id', $id);

    return success([
                     'settings' => (new SettingsPresenter()) -> item($model)
                   ]);
  }

  /**
   * Fetch Single Settings Information
   *
   * @param Request $request
   *
   * @return JsonResponse
   */
  public function general (Request $request): JsonResponse
  {
    return success([
                     'rows' => [
                       'company_name'     => \settings('company_name'),
                       'email'            => \settings('email'),
                       'website'          => \settings('website'),
                       'phone_1'          => \settings('phone_1'),
                       'phone_2'          => \settings('phone_2'),
                       'address_line_1'   => \settings('address_line_1'),
                       'address_line_2'   => \settings('address_line_2'),
                       'vat'              => \settings('vat'),
                       'iban'             => \settings('iban'),
                       'bic'              => \settings('bic'),
                       'register_number'  => \settings('register_number'),
                       'logo'             => null,
                       'large_logo'             => null,
                       'old_logo'         => ($logo = settings('logo')) ? s3($logo) : null,
                       'old_large_logo'         => ($largeLogo = settings('large_logo')) ? s3($largeLogo) : null,
                       'bank_name'        => \settings('bank_name'),
                     ]
                   ]);
  }

  public function advanced(Request $request): JsonResponse
  {
    return success([
                     'rows' => [
                       'client_invoice_number' => \settings('client_invoice_number'),
                       'partner_invoice_number' => \settings('partner_invoice_number'),
                       'invoice_partner_prefix' => \settings('invoice_partner_prefix'),
                       'invoice_client_prefix' => \settings('invoice_client_prefix'),
                       'weekly_invoice_jrxml'     => \settings('weekly_invoice_jrxml'),
                       'client_invoice_jrxml'     => \settings('client_invoice_jrxml'),
                       'invoice_terms'     => \settings('invoice_terms'),
                       'invoice_logo'     => null,
                       'old_invoice_logo'     => ($logo = settings('invoice_logo')) ? s3($logo) : null,
                       'invoice_notes'     => \settings('invoice_notes'),
                       'partner_percent'     => \settings('partner_percent'),
                       'driver_request_timeout'     => \settings('driver_request_timeout'),
                       'images_mime_types'            => \settings('images_mime_types'),
                       'last_visit_limit'          => \settings('last_visit_limit'),
                       'stripe_secret'          => \settings('stripe_secret'),
                       'driver_search_radius'          => \settings('driver_search_radius'),
                       'twilio_account_sid'   => \settings('twilio_account_sid'),
                       'twilio_messaging_sid'   => \settings('twilio_messaging_sid'),
                       'twilio_auth_token'              => \settings('twilio_auth_token'),
                       'twilio_from_id'             => \settings('twilio_from_id'),
                       'user_reset_password_timeout_in_minutes'              => \settings('user_reset_password_timeout_in_minutes'),
                     ]
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
      $settings = $this -> model -> create($request -> only([
                                                              'key',
                                                              'value'
                                                            ]));

      DB ::commit();
    } catch (Exception $exception) {
      DB ::rollBack();

      return failed([
                      $exception -> getMessage()
                    ]);
    }

    return success([
                     'id' => $settings -> id
                   ]);
  }

  /**
   * Update model data.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function updateGeneral (Request $request): JsonResponse
  {

    DB ::beginTransaction();
    try {
      foreach ($request->except(['logo', 'old_logo', 'large_logo', 'old_large_logo']) as $key => $value)
        settings($key, $value);

      if ($request->file('logo'))
        \settings('logo', uploader($request->file('logo'), 'settings', 'logo'));

      if ($request->file('large_logo'))
        \settings('large_logo', uploader($request->file('large_logo'), 'settings', 'large_logo'));

      if ($request->file('invoice_logo'))
        \settings('invoice_logo', uploader($request->file('invoice_logo'), 'settings', 'invoice_logo'));

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

  public function logo()
  {
    return success([
                     'logo' => s3(settings('logo'))
                   ]);
  }

  public function largeLogo()
  {
    return success([
                     'large_logo' => s3(settings('large_logo'))
                   ]);
  }
}
