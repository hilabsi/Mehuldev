<?php

namespace App\Modules\Settings\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Modules\Settings\ApiPresenters\OnBoardingSlidePresenter;
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
   * OnBoardingSlideController constructor.
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
                     'rows' => OnBoardingSlide::orderBy('order')->get()->map(function ($item) {
                       return (new OnBoardingSlidePresenter())->item($item);
                     })
                   ]);
  }
}
