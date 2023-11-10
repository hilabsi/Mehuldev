<?php

namespace App\Modules\Settings\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use App\Modules\Settings\Models\OnBoardingSlide;

class OnBoardingSlideController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var OnBoardingSlide
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
    $this -> model = new OnBoardingSlide();
  }

  /**
   * Show all models rows.
   */
  public function index ()
  {
    return success([
                     'rows' => OnBoardingSlide ::all()
                   ]);
  }
}
