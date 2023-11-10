<?php

namespace App\Modules\User\Controllers\Mobile;

use App\Modules\Coupon\Models\Coupon;
use App\Modules\Trip\Models\Trip;
use App\Modules\User\Enums\UserResponses;
use App\Modules\User\Models\User;
use App\Modules\User\Models\UserCard;
use App\Support\Traits\Coupons;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Support\Traits\Payments;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Support\Traits\Validations;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\CardException;
use App\Http\Controllers\Controller;
use App\Support\Traits\ModelManipulations;
use Illuminate\Validation\ValidationException;
use App\Modules\PaymentMethod\Types\WalletType;
use App\Modules\PaymentMethod\Models\PaymentMethod;
use App\Modules\PaymentMethod\Enums\PaymentMethodResponses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WalletController extends Controller
{
  use ModelManipulations;
  use Validations;
  use Payments;
  use Coupons;

  /**
   * @var PaymentMethod
   */
  protected PaymentMethod $model;

  /**
   * WalletController constructor.
   */
  public function __construct()
  {
    $this->model = new PaymentMethod();
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
  public function addCard(String $walletType, Request $request): JsonResponse
  {
    $walletType = new WalletType($walletType);

    $this->validate($request, $this->model::validations()->addCard());

    //    if ($this->validateSilently($request, [
    //      'exp_month' => new CardExpirationMonth($request->get('exp_year'))
    //    ]))
    //      return other(PaymentMethodResponses::INVALID_DATE);

    $user = auth()->guard('user')->user();

    if ($user->{$walletType . 'Wallet'}->cards()->whereNumber($request->get('number'))->first())
      return other(PaymentMethodResponses::CARD_ALREADY_ADDED);

    $user->createOrGetStripeCustomer();

    DB::beginTransaction();
    try {
      /**
       * TODO
       * 1- check stripe
       * 2- add card to customer
       * 3- authorize card
       */
      $stripe = new \Stripe\StripeClient(settings('stripe_secret'));

      $methodId = $stripe->paymentMethods->create([
                                                    'type' => 'card',
                                                    'card' => [
                                                      'number'    => $request->get('number'),
                                                      'exp_month' => $request->get('exp_month'),
                                                      'exp_year'  => $request->get('exp_year'),
                                                      'cvc'       => $request->get('cvc'),
                                                    ],
                                                  ]);

      $stripe->paymentMethods->attach(
        $methodId->id,
        ['customer' => $user->stripe_id]
      );

      $stripe->customers->update($user->stripe_id, [
        'invoice_settings' => [
          'default_payment_method' => $methodId->id,
        ]
      ]);

      $this->saveCard($walletType, $user, $methodId, $request);

      DB::commit();

    } catch (CardException $e) {

      return other(PaymentMethodResponses::INVALID_CARD);

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
  public function updateDefault(String $walletType, Request $request): JsonResponse
  {
    $walletType = new WalletType($walletType);

    $user = auth()->guard('user')->user();

    $this->validate($request, $this->model::validations()->updateDefault($user));

    if($request->get('type') === 'card' && !($card = UserCard::whereWalletType($walletType->getType())->whereUserId($user->id)->whereId($request->get('card_id'))->first()))
      throw new NotFoundHttpException;

    DB::beginTransaction();
    try {

      if ($request->get('type') !== 'card')
        $user->{$walletType. 'Wallet'}->update([
                                                 'default_payment_type' => $request->get('type'),
                                                 'card_id'              => null,
                                               ]);
      else {

        $user->{$walletType->getType(). 'Wallet'}->update([
                                                            'default_payment_type' => $request->get('type'),
                                                            'card_id'              => $request->get('card_id'),
                                                          ]);

        $stripe = new \Stripe\StripeClient(settings('stripe_secret'));

        $stripe->customers->update($user->stripe_id, [
          'invoice_settings' => [
            'default_payment_method' => UserCard::find($request->get('card_id'))->stripe_method_id,
          ]
        ]);
      }

      $user->updateFirestore([
                               ['path' => $walletType->getType().'_wallet.default', 'value' => $request->get('type')],
                               ['path' => $walletType->getType().'_wallet.active_card_id', 'value' => null]
                             ]);

      if ($request->get('type') === 'card')
        $user->updateFirestore([
                                 ['path' => $walletType->getType().'_wallet.active_card_id', 'value' => $request->get('card_id')]
                               ]);
      DB::commit();

      return success();

    } catch (\Exception $e) {

      DB::rollBack();

      return failed();
    }
  }

  /**
   * Remove a payment method.
   *
   * @param  String  $walletType
   * @param  Request  $request
   * @return JsonResponse
   * @throws ValidationException
   */
  public function removeCard(String $walletType, Request $request): JsonResponse
  {
    $walletType = new WalletType($walletType);
    $user = auth()->guard('user')->user();

    $this->validate($request, $this->model::validations()->removeCard($user->id));

    if(!($card = $user->{$walletType. 'Wallet'}->cards()->find($request->get('card_id'))))
      throw new NotFoundHttpException;

    if ($user->{$walletType. 'Wallet'}->cards->count() <= 1)
      return other(PaymentMethodResponses::CANNOT_REMOVE_CARD);

    DB::beginTransaction();
    try {

      $this->deleteCard($walletType, $card, $user);

      DB::commit();

      return success();

    } catch (Exception $exception) {

      DB::rollBack();

      return failed([
                      $exception->getCode(),
                      $exception->getMessage()
                    ]);
    }
  }

  public function setReferralCode(Request $request): JsonResponse
  {
    $this->validate($request, [
      'wallet_type' => 'required|in:business,regular',
      'invite_code' => 'required|max:191'
    ]);

    $user = auth()->guard('user')->user();

    if ($user->referral_code)
      return other(UserResponses::ALREADY_ADDED);

    if (! ($fromUser = User::where('id', '!=', $user->id)->whereInviteCode($request->get('invite_code'))->first()))
      return other(UserResponses::INVALID_CODE);

    DB::beginTransaction();
    try {

      $user->update([
                      'referral_code' => $request->get('invite_code')
                    ]);

      $user->updateFirestore([
                               ['path' => 'referral_code', 'value' => $request->get('invite_code')]
                             ]);

      $coupon = Coupon::create([
                                 'name' => 'Welcome Discount',
                                 'code' => time(),
                                 'amount' => 2,
                                 'amount_type' => 'amount',
                                 'quantity' => 1,
                                 'used_count' => 1,
                                 'max_usage_per_user' => 1,
                                 'description' => 'Referral Discount',
                                 'expiring_at' => Carbon::now()->addWeek()->format('Y-m-d'),
                               ]);

      $walletType = new WalletType($request->get('wallet_type'));

      $this->saveCoupon($walletType, $user, $coupon->id, $request);

      DB::commit();
    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([
                      $exception->getMessage()
                    ]);
    }


    return success();
  }
}
