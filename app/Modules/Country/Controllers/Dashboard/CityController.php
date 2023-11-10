<?php

namespace App\Modules\Country\Controllers\Dashboard;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Modules\Country\Models\City;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use App\Modules\Country\Enums\CityResponses;
use Illuminate\Validation\ValidationException;
use App\Modules\Country\ApiPresenters\CityPresenter;

class CityController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var City
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'countries';

  /**
   * CityController constructor.
   */
  public function __construct()
  {
    $this->model = new City();
  }

  /**
   * Show all models rows.
   *
   * @param  Request  $request
   * @return JsonResponse
   */
  public function index(Request $request)
  {
    if ($request->get('enabled'))
      return success([
                       'rows' => City::whereStatus('enabled')->get()->map(function ($item) {
                         return (new CityPresenter())->item($item);
                       })
                     ]);
    return success([
                     'rows' => City::all()->map(function ($item) {
                       return (new CityPresenter())->item($item);
                     })
                   ]);
  }

  /**
   * Fetch Single City Information
   *
   * @param Int $id
   *
   * @return JsonResponse
   */
  public function show(int $id): JsonResponse
  {
    $model = $this->shouldExists('id', $id);

    return success(
      [
        'city' => (new CityPresenter())->item($model)
      ]
    );
  }

  /**
   * Save model data.
   *
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function store(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->create());

    if ($this->model->whereCountryId($request->get('country_id'))->whereName($request->get('name'))->first())
      return other(808);

    DB::beginTransaction();
    try {
      $city = $this->model->create(
        $request->only(
          [
            'name',
            'lat',
            'lng',
            'status',
            'country_id'
          ]
        )
      );

      DB::commit();
    } catch (Exception $exception) {
      DB::rollBack();

      return failed([$exception->getMessage()]);
    }

    return success(
      [
        'id' => $city->id
      ]
    );
  }

  /**
   * Update model data.
   *
   * @param Int $id
   * @param Request $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function update(int $id, Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->edit($id));

    if ($this->model->where('id', '!=', $id)->whereCountryId($request->get('country_id'))->whereName($request->get('name'))->first())
      return other(808);

    $city = $this->shouldExists('id', $id);

    if (!$request->hasAny(
      [
        'name',
        'lat',
        'lng',
        'status',
        'country_id'
      ]
    )) {
      return other(CityResponses::NO_FIELDS_SENT);
    }


    DB::beginTransaction();
    try {
      $city->update($request->only([
                                     'name',
                                     'lat',
                                     'lng',
                                     'status',
                                     'country_id'
                                   ]));

      DB::commit();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed([
                      $exception->getCode(),
                      $exception->getMessage()
                    ]);
    }

    return success();
  }
}
