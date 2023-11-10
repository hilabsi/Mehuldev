<?php

namespace App\Modules\User\Controllers\Mobile;

use App\Modules\Car\Models\CarCategory;
use App\Modules\User\Models\User;
use App\Modules\User\Models\UserWallet;
use Exception;
use Illuminate\Http\Request;
use App\Support\Traits\Coupons;
use Illuminate\Http\JsonResponse;
use App\Services\FireStoreService;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use App\Http\Controllers\Controller;
use App\Modules\Coupon\Models\Coupon;
use App\Support\Traits\ModelManipulations;
use App\Modules\Coupon\Enums\CouponResponses;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Modules\PaymentMethod\Types\WalletType;

class CouponController extends Controller
{
  use ModelManipulations;
  use Validations;
  use Coupons;

  /**
   * @var Coupon
   */
  protected Coupon $model;

  /**
   * CustomPageController constructor.
   */
  public function __construct()
  {
    $this->model = new Coupon();
  }

  /**
   * add new payment method.
   *
   * @param  String  $walletType
   * @param  Request  $request
   *
   * @return JsonResponse
   * @throws ValidationException
   */
  public function addCoupon(String $walletType, Request $request): JsonResponse
  {
    $walletType = new WalletType($walletType);

    $this->validate($request, $this->model::validations()->addCoupon());

    $user = auth()->guard('user')->user();

    if(! ($coupon = $this->model->whereCode($request->get('code'))->first()))
      return other(CouponResponses::INVALID_CODE);

    if ($user->coupons()->whereCode($request->get('code'))->first())
      return other(CouponResponses::COUPON_ALREADY_ADDED);

    DB::beginTransaction();
    try {

      $this->saveCoupon($walletType, $user, $coupon->id, $request);

      DB::commit();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed(
        [
          $exception->getCode(),
          $exception->getMessage()
        ]
      );
    }

    return success();
  }

  /**
   * @param  String  $walletType
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function toggleActive(String $walletType, Request $request): JsonResponse
  {
    $walletType = new WalletType($walletType);

    $user = auth()->guard('user')->user();

    $this->validate($request, $this->model::validations()->toggleActive());

    $coupon = $this->shouldExists('id', $request->get('coupon_id'));

    $distance = (float)$request->get('distance'); // 10km
    $time = (int)$request->get('duration')/60 ?? 4;
    $wallet = $user->{$walletType->getType(). 'Wallet'};
    $pickup = $request->get('pickup');
    $destination = $request->get('destination');

    DB::beginTransaction();
    try {
      // if the activated coupon is the same coupon
      if($coupon->id == $wallet->active_coupon_id) {

        $wallet->update(['active_coupon_id' => null]);
      } else {

        $wallet->update(['active_coupon_id' => $coupon->id]);
      }

      $wallet = UserWallet::find($wallet->id);

      $user = User::find($user->id);

      // update firestore
      $user->updateFirestore([
                               ['path' => $walletType.'_wallet.active_coupon_id', 'value' => $wallet->active_coupon_id],
                             ]);

      // if toggle was from wallet screen (without a trip)
      if ($distance || $time) {

        // update pricing
        $categories = CarCategory::enabled()->get()->map(function ($category) use ($distance, $time, $walletType, $user, $pickup, $destination) {
          return calcCategoryPrice($category, $distance, $time, $walletType, $user, $pickup, $destination);
        });

        $user->updateFirestore([
                                 ['path' => 'trip_pricing', 'value' => $categories->toArray()],
                               ]);
      }

      DB::commit();

      return success();

    } catch (\Exception $e) {

      DB::rollBack();

      return failed([
                      $e->getMessage()
                    ]);
    }
  }
}
