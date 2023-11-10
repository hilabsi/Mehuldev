<?php

namespace App\Modules\Support\Controllers\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\Support\Models\SupportCategory;
use App\Modules\Support\Models\SupportCategoryQuestion;

class DriverController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var SupportCategory
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'support';

  /**
   * CountryController constructor.
   */
  public function __construct()
  {
    $this->model = new SupportCategory();
  }

  /**
   * Show all models rows.
   */
  public function categories()
  {
    return success([
                     'rows' =>  SupportCategory::whereLanguageId(getLanguageId(app()->getLocale()))->whereType('driver')->get()->map(function ($item) {
                       return [
                         'id'    => $item->id,
                         'name'  => $item->name,
                       ];
                     })
                   ]);
  }

  /**
   * Show all models rows.
   */
  public function questions($id)
  {
    $category = SupportCategory::whereType('driver')->find($id);

    if (! $category)
      return failed(['invalid category']);

    return success([
                     'rows' =>  SupportCategoryQuestion::whereCategoryId($category->id)->get()->map(
                       function ($item) {
                         return [
                           'id'                     => $item->id,
                           'name'                   => $item->name,
                           'type'                   => $item->type,
                           'enable_help'            => $item->enable_help,
                           'text'                   => $item->text,
                           'action'                 => $item->action,
                           'link'                   => $item->link,
                         ];
                       }
                     )
                   ]);
  }

  /**
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function sendMessage(Request $request): JsonResponse
  {
    $this->validate($request, [
      'message' => 'required|max:191',
    ]);

    return success();
  }
}
