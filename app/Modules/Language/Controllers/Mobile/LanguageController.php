<?php

namespace App\Modules\Language\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Modules\Language\ApiPresenters\LanguagePresenter;
use App\Modules\Language\Enums\LanguageResponses;
use App\Modules\Language\Enums\LanguageStatus;
use App\Modules\Language\Models\Language;
use App\Modules\Language\Models\LanguageView;
use App\Support\Exceptions\InvalidEnumerationException;
use App\Support\Traits\ModelManipulations;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LanguageController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var Language
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'languages';

  /**
   * LanguageController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Language();
  }

  /**
   * Show all models rows.
   */
  public function index (Request $request)
  {
    return success([
                     'rows' => Language ::enabled() -> get() -> map(function ($item) {
                       return (new LanguagePresenter()) -> mobile($item);
                     })
                   ]);
  }
}
