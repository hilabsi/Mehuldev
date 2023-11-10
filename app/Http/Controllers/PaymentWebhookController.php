<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{

  public function handle(Request $request)
  {
    Log::info('Payment Webhook: '.json_encode($request->all()));
    $trip = \App\Modules\Trip\Models\Trip::wherePaymentIntentId($request->get('data')['object']['id'])->first();
    $payment = \App\Modules\Trip\Models\TripTaxiPayment::wherePaymentIntentId($request->get('data')['object']['id'])->first();

    try {
      if ($trip || $payment)
        switch ($request->get('data')['object']['status']) {
          case 'requires_confirmation':
            try {
              $intent = \Stripe\PaymentIntent::retrieve($request->get('data')['object']['id']);

              $intent->confirm();

            } catch (\Exception $e) {
              if ($payment) {
                $payment->trip->updateFirestore(
                  [
                    ['path' => 'required_payment.status', 'value' => 'failed'],
                  ]
                );
              } else {
                $trip->updateFirestore(
                  [
                    ['path' => 'payment.status', 'value' => 'failed']
                  ]
                );
              }
            }
            break;
          case 'requires_action':
          case 'requires_payment_method':

            if ($payment) {
              $payment->trip->updateFirestore(
                [
                  ['path' => 'required_payment.status', 'value' => 'requires_action'],
                ]
              );
            } else {
              $trip->updateFirestore(
                [
                  ['path' => 'payment.status', 'value' => 'requires_action']
                ]
              );
            }
            break;
          case 'succeeded':


            if ($payment) {
              $payment->trip->update(['status' => 'completed']);

              $payment->trip->updateFirestore(
                [
                  ['path' => 'status', 'value' => 'completed'],
                  ['path' => 'required_payment.status', 'value' => 'success'],
                ]
              );
            } else {
              $trip->updateFirestore(
                [
                  ['path' => 'payment.status', 'value' => 'success']
                ]
              );
            }


            if ($trip && $trip->type !== 'scheduled') {
              sendDriverRequest($trip);
            } else if($trip) {
              notifyFCM([$trip->user->device_id], [
                'title'  => __('label.mobile.notifications.trip_scheduled.title'),
                'body'   => __('label.mobile.notifications.trip_scheduled.description'),
              ]);
            }
            break;

          case 'canceled':
          case 'failed':

            if ($payment) {
              $payment->trip->updateFirestore(
                [
                  ['path' => 'required_payment.status', 'value' => 'failed'],
                ]
              );
            } else {
              $trip->updateFirestore(
                [
                  ['path' => 'payment.status', 'value' => 'failed']
                ]
              );

              $trip->update(
                [
                  'status' => 'cancelled',
                  'cancel_reason' => 'Payment Failed: Payment Intent Error: '.$request->get(
                      'data'
                    )['object']['cancellation_reason'],
                ]
              );
            }
        }

      else {
        \Illuminate\Support\Facades\Log::info($request->all());
      }
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::error($e);
    }

    return response(null,200);
  }
}
