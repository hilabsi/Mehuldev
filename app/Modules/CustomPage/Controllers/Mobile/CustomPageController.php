<?php

namespace App\Modules\CustomPage\Controllers\Mobile;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Modules\CustomPage\Models\CustomPage;
use App\Support\Traits\ModelManipulations;
use App\Modules\CustomPage\Enums\CustomPageResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\CustomPage\ApiPresenters\CustomPagePresenter;

class CustomPageController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var CustomPage
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'pages';

  /**
   * CustomPageController constructor.
   */
  public function __construct ()
  {
    $this -> model = new CustomPage();
  }

  /**
   * Show all models rows.
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function index (Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->mobilePage());

    $page = $request->get('page');

    return success([
                     'content' => $this->getPage($page),
                   ]);
  }

  private function getPage($name) {

    $locale = app()->getLocale();

    $page = CustomPage::find("{$name}-{$locale}") ?? CustomPage::find("{$name}-en");

    return $page ? (new CustomPagePresenter())->item($page) : null;
  }
}
