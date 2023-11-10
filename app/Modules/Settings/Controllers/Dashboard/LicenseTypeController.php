<?php

namespace App\Modules\Settings\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use App\Modules\Settings\Models\LicenseType;

class LicenseTypeController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var LicenseType
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
    $this -> model = new LicenseType();
  }

  /**
   * Show all models rows.
   */
  public function index ()
  {
    return success([
                     'rows' => LicenseType ::all()
                   ]);
  }
}
