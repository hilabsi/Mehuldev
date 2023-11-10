<?php

namespace App\Modules\Country\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Modules\Country\ApiPresenters\CountryPresenter;
use App\Modules\Country\Enums\CountryResponses;
use App\Modules\Country\Enums\CountryStatus;
use App\Modules\Country\Models\Country;
use App\Support\Exceptions\InvalidEnumerationException;
use App\Support\Traits\ModelManipulations;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
  public function index(Request $request)
  {
    return success(
      [
        'rows' => $request->get('enabled') ? Country::enabled()->get()->map(
          function ($item) {
            return (new CountryPresenter())->item($item);
          }
        ) : Country::all()->map(
          function ($item) {
            return (new CountryPresenter())->item($item);
          }
        )
      ]
    );
  }

  /**
   * Fetch Single Country Information
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
        'country' => (new CountryPresenter())->item($model)
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

    if ($request->has('status')) {
      try {
        CountryStatus::includes($request->get('status'));
      } catch (InvalidEnumerationException $exception) {
        return other(CountryResponses::STATUS_OUT_OF_BOUND);
      }
    }

    DB::beginTransaction();
    try {
      $country = $this->model->create(
        $request->only(
          [
            'name',
            'status',
            'alpha2',
            'alpha3',
            'phone_prefix',
            'currency_id'
          ]
        )
      );

      $country->update(
        [
          'icon' => $request->file('icon')
        ]
      );

      DB::commit();
    } catch (Exception $exception) {
      DB::rollBack();

      return failed(
        [
          $exception->getMessage()
        ]
      );
    }

    return success(
      [
        'id' => $country->id
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

    $country = $this->shouldExists('id', $id);

    if (!$request->hasAny(
      [
        'name',
        'status',
        'alpha2',
        'alpha3',
        'icon',
        'phone_prefix',
        'currency_id'
      ]
    )) {
      return other(CountryResponses::NO_FIELDS_SENT);
    }

    if ($request->has('status')) {
      try {
        CountryStatus::includes($request->get('status'));
      } catch (InvalidEnumerationException $exception) {
        return other(CountryResponses::STATUS_OUT_OF_BOUND);
      }
    }

    if (($request->get('phone_prefix')) && ($request->get('phone_prefix') !== $country->phone_prefix)) {
      return other(CountryResponses::CANNOT_CHANGE_PREFIX);
    }

    DB::beginTransaction();
    try {
      $country->update(
        $request->only(
          [
            'name',
            'status',
            'alpha2',
            'alpha3',
            'phone_prefix',
            'currency_id'
          ]
        )
      );

      if ($request->file('icon')) {
        $country->update(
          [
            'icon' => $request->file('icon')
          ]
        );
      }

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
