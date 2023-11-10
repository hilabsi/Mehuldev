<?php

namespace App\Modules\Country\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Modules\Country\Models\Country;
use App\Support\Traits\ModelManipulations;
use App\Modules\Country\ApiPresenters\CountryPresenter;

class CountryController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var Country
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'countries';

  /**
   * CountryController constructor.
   */
  public function __construct()
  {
    $this->model = new Country();
  }

  /**
   * Show all models rows.
   */
  public function index()
  {
    return success(
      [
        'rows' =>  Country::enabled()->orderBy('name', 'asc')->get()->map(
          function ($item) {
            return (new CountryPresenter())->mobile($item);
          }
        )
      ]
    );
  }
}
