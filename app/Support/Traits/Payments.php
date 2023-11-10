<?php

namespace App\Support\Traits;

use App\Modules\User\Models\UserCard;
use Illuminate\Http\Request;
use App\Modules\PaymentMethod\Types\WalletType;
use Illuminate\Support\Facades\Log;

trait Payments
{
  public function saveCard(WalletType $walletType, $user, $methodId, Request $request)
  {
    $user->{$walletType . 'Wallet'}->cards()->create([
                                                       'user_id'          => $user->id,
                                                       'number'           => $request->get('number'),
                                                       'exp_month'        => $request->get('exp_month'),
                                                       'exp_year'         => $request->get('exp_year'),
                                                       'cvc'              => $request->get('cvc'),
                                                       'wallet_type'      => $walletType,
                                                       'stripe_method_id' => $methodId->id,
                                                       'image'            => s3resource(strtolower($request->get('type')).'.jpg')
                                                     ]);

    $user->updateFirestore([
                             ['path' => $walletType.'_wallet.cards', 'value' => $user->{$walletType . 'Wallet'}->cards->map(function ($item) {
                               return [
                                 'id'     => $item->id,
                                 'number' => $item->protected_number,
                                 'image'  => $item->image
                               ];
                             })->toArray()]
                           ]);
  }

  public function deleteCard($walletType, $card, $user)
  {
    $default = $user->{$walletType . 'Wallet'}->active_card_id === $card->id;

    if ($default)
      $user->{$walletType . 'Wallet'}->update(['active_card_id' => null]);

    UserCard::whereId($card->id)->delete();

    if ($default)
      $user->updateFirestore([
                               ['path' => $walletType.'_wallet.active_card_id', 'value' => $user->{$walletType . 'Wallet'}->cards()->first()->id]
                             ]);

    $user->updateFirestore([
                             ['path' => $walletType.'_wallet.cards', 'value' => $user->{$walletType . 'Wallet'}->cards()->get()->map(function ($item) {
                               return [
                                 'number' => $item->protected_number,
                                 'id'     => $item->id,
                                 'image'  => $item->image
                               ];
                             })->toArray()]
                           ]);
  }
}
