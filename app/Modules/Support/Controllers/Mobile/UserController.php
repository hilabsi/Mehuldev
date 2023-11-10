<?php

namespace App\Modules\Support\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use App\Modules\Support\Models\SupportCategory;
use App\Modules\Support\Models\SupportCategoryQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
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
                     'rows' =>  SupportCategory::whereLanguageId(getLanguageId(app()->getLocale()))->whereType('user')->get()->map(
                       function ($item) {
                         return [
                           'id'    => $item->id,
                           'name'  => $item->name,
                         ];
                       }
                     )
                   ]);
  }

  /**
   * Show all models rows.
   */
  public function questions($id)
  {
    $category = SupportCategory::find($id);

    if (!$category)
      return failed();

    return success([
                     'rows' =>  SupportCategoryQuestion::whereCategoryId($category->id)->get()->map(
                       function ($item) {
                         return [
                           'id'                     => $item->id,
                           'name'                   => $item->name,
                           'type'                   => $item->type,
                           'enable_help'            => $item->enable_help,
                           'enable_contact_driver'  => $item->enable_contact_driver,
                           'text'                   => $item->text,
                           'action'                 => $item->action,
                           'link'                   => $item->link,
                         ];
                       }
                     )
                   ]);
  }

  public function sendMessage(Request $request): JsonResponse
  {
    $this->validate($request, [
      'message' => 'required|max:191',
    ]);

    return success();
  }
}
