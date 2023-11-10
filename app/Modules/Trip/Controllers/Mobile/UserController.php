<?php

namespace App\Modules\Trip\Controllers\Mobile;

use App\Modules\EmailTemplate\Models\EmailTemplate;
use App\Modules\Invoice\Models\ClientInvoice;
use App\Modules\Partner\Mails\GeneralEmail;
use App\Modules\Settings\Models\Settings;
use App\Modules\Settings\Models\UserCancelReason;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Modules\Trip\Models\Trip;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Modules\Trip\Models\TripRoute;
use App\Modules\Car\Models\CarCategory;
use App\Modules\Trip\Models\TripRequest;
use App\Modules\Trip\Enums\TripResponses;
use App\Support\Traits\ModelManipulations;
use App\Modules\Trip\Models\ScheduledTrip;
use App\Modules\Driver\Models\DriverRating;
use App\Modules\Trip\Models\TripTaxiPayment;
use App\Modules\Trip\Jobs\SaveTripRouteImage;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class UserController extends Controller
{
  use ModelManipulations;

  /**
   *
   * @var Trip
   */
  protected $model;

  /**
   * Permissions Type
   *
   * @var string
   */
  protected $type = 'trips';

  /**
   * UserController constructor.
   */
  public function __construct ()
  {
    $this -> model = new Trip();
  }

  public function searchForDriver(Request $request)
  {
    $this->validate($request, $this->model::validations()->search());

    /**
     * TODO
     *  check if user already in a trip
     */

    /* TODO
      * step(1): Save trip object to firestore and locally
      * step(2): Every driver should be updating his location on firestore
      * we should build a query in user's current city (to find nearby driver within <5km circle)
      * step(3): Send a trip notification to them for accepting/rejecting the trip
      */

    DB::beginTransaction();
    try {
      /**
       *
       * Trip Object
       *
       * user_id (fk)
       * driver_id (nullable|fk)
       * source (location)
       * destination (location)
       * stops (relation)
       * has_stops (bool)
       * car_id (nullable)
       *
       */

      $user = auth()->guard('user')->user();

      $wallet_type = $request->get('wallet_type');
      $category = CarCategory::find($request->get('category_id'));
      $pricing = calcCategoryPrice($category, $request->get('distance'), (int)$request->get('duration') / 60, $request->get('wallet_type'), $user, $request->get('pickup')['location'], $request->get('destination')['location']);

      if (($user->{$wallet_type.'Wallet'}->default_payment_type === 'card') && !$user->{$wallet_type.'Wallet'}->card_id)
        return failed(['Invalid Card']);

      $trip = $this->model->create(
        [
          'source_location'     => $request->get('source')['location'],
          'source_address'      => $request->get('source')['address'],

          'destination_location'=> $request->get('destination')['location'],
          'destination_address' => $request->get('destination')['address'],

          'pickup_location'     => $request->get('pickup')['location'],
          'pickup_address'      => $request->get('pickup')['address'],

          'car_category_id'     => $request->get('category_id'),
          'user_id'             => $user->id,
          'time'                => $request->get('duration'),
          'distance'            => $request->get('distance'),
          'has_stops'           => !!count($request->get('stops')),
          'cost'                => $pricing['payable'],
          'wallet_type'         => $request->get('wallet_type'),
          'payment_type'        => $user->{$wallet_type.'Wallet'}->default_payment_type,
          'card_id'             => $user->{$wallet_type.'Wallet'}->card_id ?? null,
          'place_id'            => $request->get('place_id'),
          'coupon_id'           => $user->{$wallet_type.'Wallet'}->active_coupon_id,
        ]
      );

      // record coupon usage
      if ($user->{$wallet_type.'Wallet'}->active_coupon_id) {

        $usage = $user->{$wallet_type.'Wallet'}->coupons()->whereCouponId($user->{$wallet_type.'Wallet'}->active_coupon_id)->first();

        if (($usage->max_usage - $usage->used_count) <= 1) {

          $usage->increment('used_count');

          $usage->update(['finished_at' => Carbon::now()]);

          $user->{$wallet_type.'Wallet'}()->update(['active_coupon_id' => null]);

        } else {
          $usage->increment('used_count');
        }

        buildUserCoupons($user, $wallet_type);
      }

      $stops = [];
      $i = 1;
      foreach ($request->get('stops') as $stop) {
        $stops[] = [
          'trip_id' => $trip->id,
          'address' => $stop['address'],
          'location'=> new Point($stop['location']['lat'], $stop['location']['lng'], 4326),
          'order'   => $i++
        ];
      }

      $trip->setStops($stops);

      TripRoute::create(['trip_id' => $trip->id, 'enc' => $request->get('path')]);

      $document = $trip->configureFirestore();

      $trip->setDocumentHash($document->id());

      $this->dispatchNow(new SaveTripRouteImage($trip));

      $trip->user->update([
        'current_trip' => $trip->id,
      ]);

      $trip->user->updateFirestore([
        ['path' => 'current_trip', 'value'=> $document->id()]
      ]);

      // if not already saved as one of user's places


      $user->lastVisits()->create([
        'address'   => $request->get('destination')['address'],
        'location'  => new Point($request->get('destination')['location']['lat'], $request->get('destination')['location']['lng'], 4326)
      ]);

      $user->updateFirestore([
        ['path' => 'last_visited', 'value' => array_values($user->lastVisits()->orderBy('created_at', 'desc')->get()->unique(function ($item) {
          return $item['address'];
        })->take(settings('last_visit_limit') ?? 5)->map(function ($trip) {
          return [
            'address'   => $trip->address,
            'location'  => ['U' => $trip->location->getLat(), 'k' => $trip->location->getLng()]
          ];
        })->toArray())]
      ]);

//      $trip->log('created_at', $user->location, null);

      /**
       * Initiate Payment Intent
       */

      if ($trip->payment_type === 'cash') {
        $trip->updateFirestore(
          [
            $payment = [
              'path' => 'payment',
              'value' => [
                'type' => 'cash',
                'status' => 'success',
                'client_secret' => null,
                'serverside_waiting' => false
              ]
            ]
          ]
        );

        sendDriverRequest($trip);
      }
      else $payment = $this->pay($request, $trip);

      DB::commit();

    } catch (\Exception $exception) {
      DB::rollBack();

      return failed([$exception->getMessage()]);
    }

    return success([
      'document' => $document->id(),
      'trip_id'  => $trip->id,
      'payment'  => $payment
    ]);
  }

  public function calcCarsPriceList(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->carsPrices());

    $distance = (float)$request->get('distance'); // 10km
    $time = (int)$request->get('duration')/60 ?? 4;
    $wallet = $request->get('wallet_type');
    $user = auth()->guard('user')->user();
    $pickup = $request->get('pickup');
    $destination = $request->get('destination');

    $categories = CarCategory::enabled()->get()->map(function ($category) use ($distance, $time, $wallet, $user, $pickup, $destination) {
      return calcCategoryPrice($category, $distance, $time, $wallet, $user, $pickup, $destination);
    });

    auth()->guard('user')->user()->updateFirestore([
      ['path' => 'trip_pricing', 'value' => $categories->toArray()],
      ['path' => 'wallet_type', 'value' => $request->get('wallet_type')]
    ]);

    return success();
  }

  public function calcWithPickup(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->carsPricesWithPickup());

    $distance = (float)$request->get('distance'); // 10km

    $category = CarCategory::find($request->get('category_id'));
    $time = (int)$request->get('duration')/60 ?? 4;
    $wallet = $request->get('wallet_type');
    $user = auth()->guard('user')->user();
    $pickup = $request->get('pickup');
    $destination = $request->get('destination');

    return success(calcCategoryPrice($category,$distance, $time, $wallet, $user, $pickup, $destination));
  }

  public function rateDriver(Request $request)
  {
    $this->validate($request, $this->model::validations()->rateTrip());

    $user = auth()->guard('user')->user();

    $trip = Trip::whereUserId($user->id)->whereId($request->get('trip_id'))->first();

    if (($trip->status !== 'completed') || DriverRating::whereTripId($trip->id)->whereUserId($user->id)->first())
      return other(TripResponses::ALREADY_RATED);

    DriverRating::create([
      'trip_id'    => $trip->id,
      'driver_id'  => $trip->driver_id,
      'user_id'    => $user->id,
      'rating'     => $request->get('rating'),
      'comment'    => $request->get('comment') ?? null
    ]);

    $trip->driver->updateFirestore([
      ['path' => 'rating', 'value' => $trip->driver->avgRating()]
    ]);

    $user->update([
      'current_trip' => null,
    ]);

    $user->updateFirestore([
      ['path' => 'current_trip', 'value' => null],
    ]);

    if ($request->get('rating') == 5) {
      notifyFCM([$trip->driver->device_id], [
        'title'  => __('label.mobile.notifications.you_got_five_stars.title', ['name' => $user->first_name]),
        'body'   => __('label.mobile.notifications.you_got_five_stars.description'),
      ]);
    }

    $invoice = $this->createInvoice($trip->id);

    if ($invoice)
      try {

        $email = EmailTemplate::whereTitle('TRIP_INVOICE_'.strtoupper($user->language->shortcut))->first() ?? EmailTemplate::whereTitle('TRIP_INVOICE_DE')->first();

        if ($email)
          Mail::to($user)->send(new GeneralEmail(str_replace(['##first_name', '##app_link'], [$user->first_name, $invoice], $email->template), $email->subject));

      } catch (\Exception $exception) {}

    return success();
  }

  private function createInvoice($id)
  {
    $trip = Trip::find($id);

    $now = Carbon::now();

    \Illuminate\Support\Facades\DB::beginTransaction();
    try {
      $model = ClientInvoice::create(
        [
          'trip_id' => $trip->id,
          'invoice_date' => Carbon::now()->format('Y.m.d'),
          'due_date' => Carbon::now()->addWeek()->format('Y.m.d'),
          'code'  => $number = settings('client_invoice_number') ?? ClientInvoice::count() + 1,
          'total' => $trip->cost,
          'terms' => settings('invoice_terms'),
          'notes' => settings('invoice_notes'),
        ]
      );

      Settings::where('key', 'client_invoice_number')->update(['value' => $number+1]);

      $trip->update(
        [
          'client_invoice_id' => $model->id,
        ]
      );

      File::put(resource_path('client_invoice.jrxml'), settings('client_invoice_jrxml'));

      $input = resource_path('client_invoice.jrxml');

      $jasper = new \JasperPHP\JasperPHP();

      $jasper->compile($input)->execute();

      $output = storage_path($filename = 'invoices/'.$trip->id.'/'.Carbon::now()->format('Y_m_d_H_i_s'));

      if(!File::exists($s = storage_path('invoices'))){
        File::makeDirectory($s);
      }

      if(!File::exists($s = storage_path('invoices/'.$trip->id))){
        File::makeDirectory($s);
      }
      $netto = ($model->total) / 1.10;
      $options = [
        'format' => ['pdf'],
        'locale' => 'en',
        'params' => [
          "invoice_label" => __("invoice"),
          "invoice_status_label" => __("status"),
          "invoice_date_label" => __("issue_date"),
          "invoice_due_date_label" => __("due_date"),

          'trip_source_address' => $trip->source_address,
          'trip_pickup_address' => $trip->pickup_address,
          'trip_destination_address' => $trip->destination_address,
          'trip_status' => $trip->status,
          'trip_driver' => $trip->driver ? $trip->driver->name : null,
          'trip_car' => $trip->car ? getCarModel($trip->car) : null,
          'trip_cancel_reason' => $trip->cancel_reason,
          'trip_wallet_type' => $trip->wallet_type,
          'trip_payment_type' => $trip->payment_type,
          'trip_cost' => $trip->cost,
          'trip_route_image' => $trip->route_image,
          'trip_sent_iam_here' => $trip->sent_iam_here,
          'trip_distance' => $trip->distance,
          'trip_wait_time_cost' => $trip->wait_time_cost,

          "client_company_name" => $trip->user->business->company_name,
          "client_first_name" => $trip->user->first_name,
          "client_last_name" => $trip->user->last_name,
          "client_address" => $trip->user->business->address,
          "client_mail" => $trip->user->email,
          "client_phone" => $trip->user->getFullPhoneNumber(),


          "invoice_number" => \settings('invoice_client_prefix').str_pad($model->code,4,'0'),
          "invoice_date" => $model->invoice_date,
          "invoice_due_date" => $model->due_date,
          "invoice_total_amount" => formatNumber($model->total),
          "invoice_total_net_amount" => formatNumber($netto),
          "invoice_total_vat_amount" => formatNumber($netto*0.10),
          "invoice_terms" => str_replace('"', '', $model->terms),
          "invoice_notes" => str_replace('"', '', $model->notes),

          "company_name" => settings('company_name'),
          "company_adress_line_one" => settings('address_line_1'),
          "company_adress_line_two" => settings('address_line_2'),
          "company_phone_one" => settings('phone_1'),
          "company_phone_two" => settings('phone_2'),
          "company_email" => settings('email'),
          "company_website" => settings('website'),
          "company_uid" => settings('vat'),
          "company_fn" => settings('register_number'),
          "company_bank_name" => settings('bank_name'),
          "company_iban" => settings('iban'),
          "company_bic" => settings('bic'),

          'header_image'              => s3(settings('invoice_logo') ?? settings('logo')),
        ],
      ];

      $x = $jasper->process(str_replace('jrxml', 'jasper', $input), $output, ['pdf'], $options['params'])->execute();

      Storage::disk('s3')->put($filename.'.pdf', File::get($output.'.pdf'));

      $model->update(['download_url' => $url = s3($filename.'.pdf')]);

      \Illuminate\Support\Facades\DB::commit();

      return $url;

    } catch (\Exception $e) {

      DB::rollBack();

      return false;
    }
  }


  public function cancelTrip(Request $request): JsonResponse
  {
    $this->validate($request, $this->model::validations()->cancelTrip());

    $trip = Trip::find($request->get('trip_id'));

    DB::beginTransaction();
    try {
      $trip->update(['status' => 'cancelled', 'cancel_reason' => $request->get('cancel_reason')]);

      $trip->updateFirestore([
        ['path' => 'status', 'value' => 'cancelled']
      ]);

      $trip->updateFirestore(
        [
          ['path' => 'status', 'value' => 'cancelled']
        ]
      );

      if ($trip->user->current_trip === $trip->id) {

        $trip->user->update([
          'current_trip' => null,
        ]);

        $trip->user->updateFirestore(
          [
            ['path' => 'current_trip', 'value' => null]
          ]
        );

        if($trip->driver) {
          $trip->driver->update([
            'current_trip' => null,
          ]);

          $trip->driver->updateFirestore([
            ['path' => 'current_trip', 'value' => null]
          ]);

          notifyFCM(
            [$trip->driver->device_id],
            [
              'title' => __('label.mobile.notifications.trip_cancelled.title'),
              'body' => __('label.mobile.notifications.trip_cancelled.description'),
            ]);
        }
      }

      if ($tripRequest = TripRequest::whereTripId($trip->id)->orderBy('created_at', 'desc')->first()) {
        if (($tripRequest->status === 'pending') && $tripRequest->driver) {
          $tripRequest->driver->updateFirestore([
            ['path' => 'request', 'value' => null]
          ]);
          $tripRequest->delete();
        }
      }

      /**
       * attempt to refund
       */
      if ($trip->payment_intent_id) {
        \Stripe\Stripe::setApiKey(settings('stripe_secret'));

        $re = \Stripe\Refund::create(
          [
            'payment_intent' => $trip->payment_intent_id,
          ]
        );
      }

      try {

        $email = EmailTemplate::whereTitle('TRIP_CANCEL_CONFORMATION_'.strtoupper($trip->user->language->shortcut))->first() ?? EmailTemplate::whereTitle('TRIP_CANCEL_CONFORMATION_DE')->first();

        if ($email)
          Mail::to($trip->user)->send(new GeneralEmail(str_replace(['##first_name', '##app_link'], [$trip->user->first_name, env('PLAYSTORE_URL')], $email->template), $email->subject));

      } catch (\Exception $exception) {}

      DB::commit();

      return success();

    } catch (\Exception $exception) {

      DB::rollBack();

      return failed([$exception->getMessage()]);
    }

  }

  public function cancelReasons(Request $request): JsonResponse
  {
    return success([
      'reasons' => UserCancelReason::whereLanguageId(getLanguageId(app()->getLocale()))->get()->map(function ($item) {
        return $item->reason;
      })
    ]);
  }

  public function pay(Request $request = null, $trip = null)
  {
    \Stripe\Stripe::setApiKey(settings('stripe_secret'));

    $intent = null;
    try {

      if ($trip->cost) {
        if ($request && $request->get('payment_intent_id')) {

          $intent = \Stripe\PaymentIntent::retrieve($request->get('payment_intent_id'));

          $intent->confirm();

        } else if ($trip->payment_type === 'card') {

          # Create the PaymentIntent
          $intent = \Stripe\PaymentIntent::create([
            'customer'            => $trip->user->stripe_id,
            'payment_method'      => $trip->card->stripe_method_id,
            'amount'              => $trip->cost * 100, // to cents
            'error_on_requires_action' => false,
            'currency'            => 'eur',
            'confirm'             => true,
          ]);

        } elseif (!in_array($trip->payment_type,  ['cash', 'card'])) {

          # Create the PaymentIntent
          $intent = \Stripe\PaymentIntent::create([
            'error_on_requires_action' => false,
            'customer'            => $trip->user->stripe_id,
            'amount'              => $trip->cost * 100, // to cents
            'currency'            => 'eur',
          ]);

        }

        if ($intent) {

          if (in_array($intent->status, ['requires_payment_method', 'requires_action'])) {

            $status = 'requires_action';

          } else if ($intent->status == 'succeeded') {
            # The payment didn’t need any additional actions and completed!
            # Handle post-payment fulfillment

            $status = 'success';

          } else if($intent->status === 'canceled') {

            $status = 'failed';

            # Invalid status
            $trip->update(
              ['status' => 'cancelled', 'cancel_reason' => 'Payment Failed: payment intent status: '.$intent->status]
            );

            $trip->updateFirestore(
              [
                ['path' => 'status', 'value' => 'cancelled']
              ]
            );
          }

          $trip->updateFirestore([['path' => 'payment', 'value' => $payment = [
            'payment_intent_id' => $intent->id,
            'status'            => $status ?? 'pending',
            'type'              => $trip->payment_type,
            'client_secret'     => $intent->client_secret,
            'serverside_waiting'=> $trip->payment_type === 'card',
            'image'             => $trip->payment_type === 'card' ? $trip->card->image : null,
          ]]]);

          $trip->update([
            'payment_intent_id' => $intent->id
          ]);

          return $payment;
        }
      } else {

        $trip->updateFirestore([['path' => 'payment', 'value' => $payment = [
          'payment_intent_id' => null,
          'status'            => 'success',
          'type'              => $trip->payment_type,
          'client_secret'     => null,
          'serverside_waiting'=> false,
          'image'             => $trip->payment_type === 'card' ? $trip->card->image : null,
        ]]]);

        sendDriverRequest($trip);

        return $payment;
      }

      return [];

    } catch (\Stripe\Exception\ApiErrorException $e) {

      $trip->update(['status' => 'cancelled', 'cancel_reason' => 'Payment Failed: '.$e->getMessage()]);

      $trip->updateFirestore([
        ['path' => 'status', 'value' => 'cancelled']
      ]);

      return [];
    }
  }

  public function attachGooglePayPaymentMethod(Request $request)
  {
    $this->validate($request, [
      'request_id'  => 'required|max:191',
      'card_id'     => 'required|max:191',
      'trip_id'     => 'required'
    ]);

    \Stripe\Stripe::setApiKey(settings('stripe_secret'));

    $trip = $this->model->find($request->get('trip_id')) ?? ScheduledTrip::find($request->get('trip_id'));

    if (!$trip)
      return failed(['trip_not_found']);

    try {
      $intent = \Stripe\PaymentIntent::retrieve($trip->payment_intent_id);

      $methodId = PaymentMethod::create(
        [
          'type' => 'card',
          'card' => [
            'token' => $request->get('request_id'),
          ],
        ]
      );

      PaymentIntent::update($intent->id, [
        'payment_method' => $methodId
      ]);

      $intent = \Stripe\PaymentIntent::retrieve($intent->id);

      $intent->confirm();

      return success();

    } catch (\Exception $exception) {

      Log::info($exception);

      return failed([
        $exception->getMessage()
      ]);
    }
  }

  public function finishPaymentClientSideInteraction(Request $request): JsonResponse
  {
    $this->validate($request, [
      'trip_id'           => 'required|exists:d_trips,id',
      'payment_intent_id' => 'required|exists:d_trips,payment_intent_id',
      'client_secret'     => 'required',
    ]);

    DB::beginTransaction();
    try {
      $trip = $this->model::where('payment_intent_id', $request->get('payment_intent_id'))->find(
        $request->get('trip_id')
      );

      \Stripe\Stripe::setApiKey(settings('stripe_secret'));

      $intent = PaymentIntent::retrieve($trip->payment_intent_id);

      if (!$trip && $intent->client_secret === $request->get('client_secret')) {
        return failed();
      }

      $trip->updateFirestore(
        [
          ['path' => 'payment.serverside_waiting', 'value' => true]
        ]
      );

      $trip->update(['serverside_waiting' => true]);

      DB::commit();

    } catch (\Exception $exception) {

      DB::rollBack();
      return failed([
        $exception->getMessage()
      ]);
    }

    return success();
  }

  public function finishPaymentClientSideInteractionRequiredPayment(Request $request): JsonResponse
  {
    $this->validate($request, [
      'trip_id'           => 'required|exists:d_trips,id',
      'payment_id'        => 'required|exists:d_trip_taxi_payments,id',
      'payment_intent_id' => 'required|exists:d_trip_taxi_payments,payment_intent_id',
      'client_secret'     => 'required',
    ]);

    DB::beginTransaction();
    try {
      $payment = TripTaxiPayment::where('payment_intent_id', $request->get('payment_intent_id'))
        ->whereTripId($request->get('trip_id'))
        ->find($request->get('payment_id'));

      \Stripe\Stripe::setApiKey(settings('stripe_secret'));

      $intent = PaymentIntent::retrieve($payment->payment_intent_id);

      if (!$payment && $intent->client_secret === $request->get('client_secret')) {
        return failed();
      }

      $payment->trip->updateFirestore(
        [
          ['path' => 'required_payment.serverside_waiting', 'value' => true]
        ]
      );

      $payment->trip->update(['serverside_waiting' => true]);

      DB::commit();

    } catch (\Exception $exception) {

      DB::rollBack();
      return failed([
        $exception->getMessage()
      ]);
    }

    return success();
  }

  public function schedule(Request $request)
  {
    $this->validate($request, ScheduledTrip::validations()->search());

    /**
     * TODO
     *  check if user already in a trip
     */

    /* TODO
      * step(1): Save trip object to firestore and locally
      * step(2): Every driver should be updating his location on firestore
      * we should build a query in user's current city (to find nearby driver within <5km circle)
      * step(3): Send a trip notification to them for accepting/rejecting the trip
      */

    DB::beginTransaction();
    try {
      /**
       *
       * Trip Object
       *
       * user_id (fk)
       * driver_id (nullable|fk)
       * source (location)
       * destination (location)
       * stops (relation)
       * has_stops (bool)
       * car_id (nullable)
       *
       */

      $user = auth()->guard('user')->user();
      $category = CarCategory::find($request->get('category_id'));
      $pricing = calcCategoryPrice($category, $request->get('distance'), (int)$request->get('duration')/60, $request->get('wallet_type'), $user, $request->get('pickup')['location'], $request->get('destination')['location']);
      $wallet_type = $request->get('wallet_type');

      $trip = $this->model::create(
        [
          'source_location'     => $request->get('source')['location'],
          'source_address'      => $request->get('source')['address'],

          'destination_location'=> $request->get('destination')['location'],
          'destination_address' => $request->get('destination')['address'],

          'pickup_location'     => $request->get('pickup')['location'],
          'pickup_address'      => $request->get('pickup')['address'],

          'car_category_id'     => $request->get('category_id'),
          'user_id'             => $user->id,
          'has_stops'           => !!count($request->get('stops')),
          'cost'                => $pricing['payable'],
          'time'                => (int)$request->get('duration')/60,
          'distance'            => $request->get('distance'),
          'wallet_type'         => $wallet_type,
          'payment_type'        => $user->{$wallet_type.'Wallet'}->default_payment_type,
          'card_id'             => $user->{$wallet_type.'Wallet'}->card_id ?? null,
          'place_id'            => $request->get('place_id'),
          'scheduled_on'        => $request->get('date'),
          'coupon_id'           => $user->{$wallet_type.'Wallet'}->active_coupon_id,
          'type' => 'scheduled'
        ]
      );

      // record coupon usage
      if ($user->{$wallet_type.'Wallet'}->active_coupon_id) {

        $usage = $user->{$wallet_type.'Wallet'}->coupons()->whereCouponId($user->{$wallet_type.'Wallet'}->active_coupon_id)->first();

        if (($usage->max_usage - $usage->used_count) <= 1) {

          $usage->increment('used_count');

          $usage->update(['finished_at' => Carbon::now()]);

          $user->{$wallet_type.'Wallet'}()->update(['active_coupon_id' => null]);

        } else {
          $usage->increment('used_count');
        }

        buildUserCoupons($user, $wallet_type);
      }

      $stops = [];
      $i = 1;
      foreach ($request->get('stops') as $stop) {
        $stops[] = [
          'trip_id' => $trip->id,
          'address' => $stop['address'],
          'location'=> new Point($stop['location']['lat'], $stop['location']['lng'], 4326),
          'order'   => $i++
        ];
      }

      $trip->setStops($stops);

      TripRoute::create(['trip_id' => $trip->id, 'enc' => $request->get('path')]);


      $document = $trip->configureFirestore();

      $trip->setDocumentHash($document->id());

      $this->dispatchNow(new SaveTripRouteImage($trip));

      // if not already saved as one of user's places

      $user->lastVisits()->create([
        'address'   => $request->get('destination')['address'],
        'location'  => new Point($request->get('destination')['location']['lat'], $request->get('destination')['location']['lng'], 4326)
      ]);

      $user->updateFirestore([
        ['path' => 'last_visited', 'value' => array_values($user->lastVisits()->orderBy('created_at', 'desc')->get()->unique(function ($item) {
          return $item['address'];
        })->take(settings('last_visit_limit') ?? 5)->map(function ($trip) {
          return [
            'address'   => $trip->address,
            'location'  => ['U' => $trip->location->getLat(), 'k' => $trip->location->getLng()]
          ];
        })->toArray())]
      ]);

//      $trip->log('created_at', $user->location, null);

      if ($trip->payment_type === 'cash') {
        $trip->updateFirestore(
          [
            [
              'path' => 'payment',
              'value' => $payment = [
                'type' => 'cash',
                'status' => 'success',
                'client_secret' => null,
                'serverside_waiting' => false
              ]
            ]
          ]
        );
      }
      else $payment = $this->pay($request, $trip);

      /**
       * Initiate Payment Intent
       */
      DB::commit();

    } catch (\Exception $exception) {
      DB::rollBack();

      return failed([$exception->getMessage()]);
    }

    if ($payment['status'] === 'success')
      notifyFCM([$user->device_id], [
        'title'  => __('label.mobile.notifications.trip_scheduled.title'),
        'body'   => __('label.mobile.notifications.trip_scheduled.description'),
      ]);

    return success([
      'document' => $document->id(),
      'trip_id' => $trip->id,
      'payment' => $payment
    ]);
  }

  public function requestRequiredPayment(Request $request)
  {
    $this->validate($request, [
      'trip_id' => 'required|exists:d_trips,id',
    ]);

    $trip = $this->model->whereStatus('requires_payment')->find($request->get('trip_id'));

    if (! $trip)
      return other(1400);

    \Stripe\Stripe::setApiKey(settings('stripe_secret'));

    $cost = $trip->cost;

    $cost *= 100;

    $intent = null;
    try {

      if ($trip->payment_type === 'card') {

        # Create the PaymentIntent
        $intent = \Stripe\PaymentIntent::create([
          'customer'            => $trip->user->stripe_id,
          'payment_method'      => $trip->card->stripe_method_id,
          'amount'              => $cost, // to cents
          'error_on_requires_action' => false,
          'currency'            => 'eur',
          'confirm'             => true,
        ]);

      } elseif (!in_array($trip->payment_type,  ['cash', 'card'])) {

        # Create the PaymentIntent
        $intent = \Stripe\PaymentIntent::create([
          'error_on_requires_action' => false,
          'customer'            => $trip->user->stripe_id,
          'amount'              => $cost, // to cents
          'currency'            => 'eur',
        ]);

      }

      $payment = TripTaxiPayment::create([
        'client_secret'     => null,
        'payment_intent_id' => null,
        'type'              => $trip->payment_type,
        'trip_id'           => $trip->id,
        'amount'            => $cost,
        'user_id'           => $trip->user->id,
      ]);

      /**
       * TODO: add model for wait time models.
       */

      $trip->updateFirestore([['path' => 'required_payment', 'value' => [
        'client_secret'     => null,
        'payment_intent_id' => null,
        'status'            => 'pending',
        'amount'            => $cost,
        'serverside_waiting' => false,
        'type'              => $trip->payment_type,
        'image'             => $trip->payment_type === 'card' ? $trip->card->image : null,
      ]]]);


      if ($intent) {

        if ($intent->status == 'requires_payment_method') {

          $status = 'requires_action';

        } else if ($intent->status == 'succeeded') {
          # The payment didn’t need any additional actions and completed!
          # Handle post-payment fulfillment

          $status = 'success';

        } else {

          $status = 'failed';

          # Invalid status
          $payment->update(['status' => 'failed', 'reason' => 'Payment Failed: payment intent status: '.$intent->status]);

        }

        $trip->updateFirestore([
          ['path' => 'required_payment.status', 'value' => $status],
          ['path' => 'required_payment.client_secret', 'value' => $intent->client_secret],
          ['path' => 'required_payment.payment_intent_id', 'value' => $intent->id],
        ]);

        $payment->update([
          'status'             => $status,
          'payment_intent_id'  => $intent->id,
          'client_secret'      => $intent->client_secret
        ]);

        return success([
          'payment' => [
            'payment_id'         => $payment->id,
            'payment_intent_id'  => $intent->id,
            'amount'             => round($cost/100, 2),
            'type'               => $trip->payment_type,
            'client_secret'      => $intent->client_secret,
            'image'             => $trip->payment_type === 'card' ? $trip->card->image : null,
          ]
        ]);
      }

    } catch (\Stripe\Exception\ApiErrorException $e) {

      if (isset($payment))
        $payment->update(['status' => 'failed', 'reason' => 'Payment Failed: '.$e->getMessage()]);

      $trip->updateFirestore([
        ['path' => 'required_payment.status', 'value' => 'failed']
      ]);
    }

    return failed();
  }

  public function attachGooglePayPaymentMethodForRequiredPayment(Request $request)
  {
    $this->validate($request, [
      'request_id'  => 'required|max:191',
      'card_id'     => 'required|max:191',
      'payment_id'  => 'required|exists:d_trip_taxi_payments,id',
      'trip_id'     => 'required|exists:d_trips,id'
    ]);

    \Stripe\Stripe::setApiKey(settings('stripe_secret'));

    $payment = TripTaxiPayment::whereTripId($request->get('trip_id'))->whereId($request->get('payment_id'))->first();

    Log::info($payment);

    if (! $payment)
      return failed();

    try {
      $intent = \Stripe\PaymentIntent::retrieve($payment->payment_intent_id);

      $methodId = PaymentMethod::create(
        [
          'type' => 'card',
          'card' => [
            'token' => $request->get('request_id'),
          ],
        ]
      );

      PaymentIntent::update($intent->id, [
        'payment_method' => $methodId
      ]);

      $intent = \Stripe\PaymentIntent::retrieve($intent->id);


      $intent->confirm();

    } catch (\Exception $exception) {

      return failed([$exception->getMessage()]);
    }

    return success();
  }
}
