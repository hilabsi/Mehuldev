<?php

namespace App\Modules\Coupon\ApiPresenters;

use App\Modules\User\ApiPresenters\UserPresenter;
use App\Modules\User\Models\UserCoupon;
use App\Support\Contracts\ApisPresenter;
use Carbon\Carbon;

class CouponPresenter extends ApisPresenter
{
  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present (): array
  {
    return $this->collection->map(function ($item) {
      return $this->item($item);
    })-> toArray();
  }

  public function item ($item)
  {
    return [
      'id'          => $item->id,
      'name'        => $item->name,
      'expiring_at' => $item->expiring_at,
      'formatted_expiring_at' => Carbon::createFromFormat('Y-m-d', $item->expiring_at)->format('Y.m.d'),
      'description' => $item->description,
      'max_usage_per_user' => $item->max_usage_per_user,
      'amount'      => $item->amount,
      'used_count'      => UserCoupon::with('user')->whereCouponId($item->id)->count(),
      'formatted_amount' => $item->amount_type === 'percent' ? $item->amount.'%' : formatNumber($item->amount),
      'quantity'    => $item->quantity,
      'amount_type' => $item->amount_type,
      'code'        => $item->code,
      'usage'  => UserCoupon::with('user')->whereCouponId($item->id)->get()->map(function ($item) {
        return [
          'user' => $item->user ? (new UserPresenter())->shortPresent($item->user) : null,
          'used_count' => $item->used_count,
          'wallet_type' => $item->wallet_type,
          'finished_at' => Carbon::parse($item->finished_at)->format('Y.m.d H:i:s')
        ];
      })
    ];
  }
}
