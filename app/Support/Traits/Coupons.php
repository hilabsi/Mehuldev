<?php

namespace App\Support\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Modules\Coupon\Models\Coupon;
use App\Modules\PaymentMethod\Types\WalletType;

trait Coupons
{
  public function saveCoupon(WalletType $walletType, $user, $couponId, Request $request)
  {
    $coupon = Coupon::find($couponId);

    $user->{$walletType . 'Wallet'}->coupons()->create([
                                                         'user_id'          => $user->id,
                                                         'coupon_id'        => $couponId,
                                                         'max_usage'        => $coupon->max_usage_per_user,
                                                         'code'             => $coupon->code,
                                                         'wallet_type'      => $walletType,
                                                       ]);
    $user->updateFirestore([
                             ['path' => $walletType.'_wallet.coupons', 'value' => $user->{$walletType.'Wallet'}->coupons()->available()->get()->map(function ($item) {
                               return [
                                 'description'  => $item->coupon->description,
                                 'amount'       => ($item->coupon->amount_type === 'percent' ? '%':'€').$item->coupon->amount,
                                 'amount_type'  => $item->coupon->amount_type,
                                 'expiring_at'  => Carbon::parse($item->coupon->expiring_at)->format('M j'),
                                 'max_usage'    => $item->max_usage,
                                 'name'         => $item->coupon->name,
                                 'id'           => $item->coupon->id,
                               ];
                             })->toArray()]
                           ]);

  }
}
