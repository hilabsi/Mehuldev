<?php

use App\Modules\Driver\Models\Driver;
use App\Modules\Trip\Models\Trip;
use App\Modules\Trip\Models\TripRequest;
use App\Modules\User\Models\UserRating;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use App\Modules\Settings\Models\Settings;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging\RawMessageFromArray;

if (! function_exists('nullable')) {

  /**
   * Check if value is null or empty string.
   *
   * @param $var
   * @return bool|null
   */
  function nullable($var): ?bool
  {
    return isset($var) ? (strlen($var) === 0 ? null : $var): null;
  }
}

/**
 * Success response
 */
if (! function_exists('success')) {

  function success(Array $data = []): JsonResponse {

    if (count($data))
      return response()->json([
                                'status'    => 200,
                                'data'      => $data,
                              ],200,[],JSON_PRETTY_PRINT);

    return response()->json([
                              'status'    => 200,
                            ],200,[],JSON_PRETTY_PRINT);
  }
}

/**
 * Failed response
 */
if (! function_exists('failed')) {

  function failed($params = []): JsonResponse {

    return response()->json([
                              'status' => 500,
                              'params' => $params
                            ],200,[],JSON_PRETTY_PRINT);
  }
}

/**
 * Generate random token
 */
if (! function_exists('createToken')) {

  function createToken($length = null): string {
    if ($length)
      return substr(uniqid(time()), 0, $length);
    return uniqid(time());
  }
}

/**
 * Clean urls.
 *
 * @param Request $request
 * @param string $url
 * @return string
 */
function checkHost(Request $request, string $url): string {

  $base = str_replace(['https://', 'http://'], '', env('APP_URL'));

  if (($base === optional(parse_url($url))['host']) && $base !== $request->getHost()) {

    return str_replace($base, $request->getHost(), $url);
  }

  return $url;
}

function numFormatted($number) {
  return number_format($number, 0, '.', ',' );
}

/**
 * Translate a label.
 *
 * @param String $label
 * @return String
 */
function labels(String $label): String {
  if ($label)
    return __('labels.'.$label);
  return '';
}

/**
 * Format responses with custom codes.
 *
 * @param Int $code
 * @param array $data
 * @return JsonResponse
 */
function other(Int $code, array $data = []): JsonResponse {

  if (count($data))
    return response()->json([
                              'status' => $code,
                              'data'  => $data,
                            ],200,[],JSON_PRETTY_PRINT);

  return response()->json([
                            'status' => $code,
                          ],200,[],JSON_PRETTY_PRINT);
}

/**
 * Upload files
 */
if (! function_exists('uploader')) {

  function uploader(UploadedFile $file, String $type, String $id)
  {
    return $file->storePublicly($type.'/'.$id, 's3');
  }
}

function linkStorage() {

  symlink(__DIR__.'/../../storage/app/public', __DIR__.'/../../public/storage');
}

function s3($path) {
  return 'https://'.env('AWS_BUCKET').'.s3.eu-central-1.amazonaws.com/'.$path;
}

if ( ! function_exists('config_path'))
{
  /**
   * Get the configuration path.
   *
   * @param  string $path
   * @return string
   */
  function config_path($path = ''): string
  {
    return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
  }
}

function getCarModel($car) {
  $combine = $car->brand->title . ' - ' . $car->model->title;

  if(strlen($combine) > 15)
    return $car->model->title;
  return $combine;
}

if (! function_exists('settings')) {

  function settings($key, $value = null, $nullable = false) {

    $settings = Settings::where(['key' => $key])->first();

    if ($settings) {

      if (!$nullable) {

        if ($value !== null) {
          $settings->update(['value' => $value]);
        }
      } else {
        $settings->update(['value' => $value]);
      }

      return $settings->value;
    }

    if ($value);
    Settings::create(['key' => $key, 'value' => $value]);

    return $value;
  }
}

function getImageValidtion() {
  return '|image|mimes:jpg,png,jpeg'; #settings('images_mime_types');
}

function generateRandomCode($length = 20) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

function generateRandomCodeNumber($length = 20) {
  $characters = '0123456789';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }
  return $randomString;
}

function resetPassword(\Illuminate\Contracts\Auth\Authenticatable $model) {

  $beautymail = app()->make(Snowfire\Beautymail\Beautymail::class);

  $beautymail->send('emails.welcome', [], function($message) {
    $message
      ->from('bar@example.com')
      ->to('foo@example.com', 'John Smith')
      ->subject('Welcome!');
  });
}

function notifyFCM(array $devices, array $data) {
  $messaging = app('firebase.messaging');

  foreach ($devices as $device) {
    try {
      $message = new RawMessageFromArray(
        [
          'token' => $device,
          'notification' => [
            'title' => $data['title'],
            'body' => $data['body'],
          ],
          'data' => [
            'title' => $data['title'],
            'body' => $data['body'],
          ],
          'android' => [
            'ttl' => '3600s',
            'priority' => 'high',
            'notification' => [
              'title' => $data['title'],
              'body' => $data['body'],
              'tag' => 'trip',
              'vibrate_timings' => ['2.0s', '2.0s', '4.0s'],
              'ticker' => $data['title']
            ],
          ],
          'apns' => [
            'headers' => [
              'apns-priority' => '10',
            ],
            'payload' => [
              'aps' => [
                'alert' => [
                  'title' => $data['title'],
                  'body' => $data['body'],
                ],
                'badge' => 42,
              ],
            ],
          ]
        ]
      );

      $messaging->send($message);

      \Illuminate\Support\Facades\Log::info('notify:: sent to '.$device);
    } catch (\Exception $e) {
      \Illuminate\Support\Facades\Log::error('notify-error:: when sending to '.$device);
    }
  }
}

function updateReferralSharingText() {
  auth()->guard('user')->user()->updateFirestore([
                                                   ['path' => 'sharing_text', 'value' => 'Join me on Lobi on :link']
                                                 ]);
}

function buildUserCoupons($user, $wallet_type) {
  $user->updateFirestore([
                           ['path' => $wallet_type.'_wallet.active_coupon_id', 'value' => $user->{$wallet_type.'Wallet'}->active_coupon_id],
                         ]);

  $user->updateFirestore([
                           ['path' => $wallet_type.'_wallet.coupons', 'value' => $user->{$wallet_type.'Wallet'}->coupons()->available()->get()->map(function ($item) {
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

function formatNumber($number) {
  return "€" . number_format($number, 2, ',', '.');
}

function calcCategoryPrice($category, $distance, $time, $wallet, $user, array $pickup = null, array $destination = null): array {

  $wallet = $user->{$wallet.'Wallet'};
  $wallet->refresh();

  $user->refresh();

  $pricing = null;
  if ($pickup && $destination)
    try {

      $guzzle = new \GuzzleHttp\Client();

      $from = json_decode($guzzle->get('https://api.bigdatacloud.net/data/reverse-geocode-client?latitude='.$pickup['lat'].'&longitude='.$pickup['lat'].'&localityLanguage=en')->getBody());
      $to = json_decode($guzzle->get('https://api.bigdatacloud.net/data/reverse-geocode-client?latitude='.$destination['lat'].'&longitude='.$destination['lat'].'&localityLanguage=en')->getBody());

      $pricing = \App\Modules\Car\Models\CarCategoryCityPricing::where(['from_city' => $from->city, 'to_city' => $to->city, 'category_id' => $category->id])->first();

    } catch (\Exception $exception) {

    }

  // calculating trip base price
  $price = ($pricing ?? $category)->start_price + (($distance * ($pricing ?? $category)->km_price)/1000) + ($time * ($pricing ?? $category)->minute_price);

  // check if it meets the minimum
  $price = round($price < ($pricing ?? $category)->minimum_price ? ($pricing ?? $category)->minimum_price : $price, 2);

  $coupon = $wallet->activeCoupon;

  $shouldUseCoupon = false;

  // if there is an active coupon, check it usage first
  if ($coupon) {

    $couponUsage = $user->coupons()->whereCouponId($coupon->id)->first(); // get user usage

    $shouldUseCoupon = $couponUsage && ($couponUsage->max_usage > $couponUsage->used_count);

    if (! $shouldUseCoupon)  {

      $coupon->update(['finished_at' => Carbon::now()]);

      $wallet->update([
                        'active_coupon_id' => null
                      ]);

      buildUserCoupons($user, $wallet->type);
    }
  }

  if ($category->type === 'self_calculated') {

    $shift_percentage = ($price*(($pricing ?? $category)->range_percent/100));
    $min_price = round($price - $shift_percentage, 2);
    $max_price = round($price + $shift_percentage, 2);

    $numeric_price = "{$min_price}-{$max_price}";
    $text_price = formatNumber($min_price).'-'.formatNumber($max_price);

    if ($shouldUseCoupon) {

      if ($coupon->amount_type === 'percent') {

        $min_price_after_discount = round($min_price - ($min_price * ($coupon->amount / 100)), 2);
        $max_price_after_discount = round($max_price - ($max_price * ($coupon->amount / 100)), 2);
      } else {

        $min_price_after_discount = round($min_price - $coupon->amount,2);
        $max_price_after_discount = round($max_price - $coupon->amount,2);
      }

      if ($min_price_after_discount < 1) {
        $min_price_after_discount = 1.00;
      }

      if ($max_price_after_discount < 1) {
        $max_price_after_discount = 1.00;

        if ($coupon->amount_type === 'percent') {
          $max_price_after_discount += $max_price * ($coupon->amount / 100);
        } else {
          $max_price_after_discount += $coupon->amount;
        }
      }

      $numeric_price_after_discount = "{$min_price_after_discount}-{$max_price_after_discount}";
      $text_price_after_discount = formatNumber($min_price_after_discount).'-'.formatNumber($max_price_after_discount);
    }

    // if taxi, pay 0 at first
    $payable = 0;
  }
  else {

    if ($shouldUseCoupon) {
      if ($coupon->amount_type === 'percent') {
        $price_after_discount = round($price - ($price * ($coupon->amount / 100)), 2);
      } else {
        $price_after_discount = round($price - $coupon->amount,2);
      }

      if($price_after_discount < 1)
        $price_after_discount = 1.00;

      $numeric_price_after_discount = $price_after_discount;
      $text_price_after_discount = formatNumber($price_after_discount);
    }

    $numeric_price = $price;
    $text_price = formatNumber($price);

    $payable = $shouldUseCoupon ? $numeric_price_after_discount : $price;
  }

  return [
    'category_id'                   => $category->id,
    'type'                          => $category->name,
    'payments'                      => $category->type,
    'price'                         => $shouldUseCoupon ? $text_price_after_discount : $text_price,
    'numeric_price'                 => $shouldUseCoupon ? $numeric_price_after_discount : $numeric_price,
    'price_before_discount'         => $shouldUseCoupon ? $text_price : null,
    'numeric_price_before_discount' => $shouldUseCoupon ? $numeric_price : null,
    'payable'                       => $payable,
    'seats'                         => __('label.mobile.persons', ['1' => $category->seats]),
    'image'                         => $category->image,
    'time'                          => __('label.mobile.min', ['1' => calcNearbyCategoryCars($category, $pickup)]),
  ];
}

function calcNearbyCategoryCars($category, $pickup) {

  if ($pickup) {

    $point = new \Grimzy\LaravelMysqlSpatial\Types\Point($pickup['lat'], $pickup['lng'], 4326);

    $car = $category->cars()->whereNotNull('current_session')->distanceSphere('location', $point, \settings('driver_search_radius'))->orderByDistanceSphere('location', $point)->first();

    if ($car) {

      $car = \App\Modules\Car\Models\Car::selectRaw('id, location, st_distance_sphere(location, ST_GeomFromText(\'POINT('.$point.')\', 4326, \'axis-order=long-lat\')) as distance')->whereId($car->id)->first();

      $mins = ceil(($car->distance/1000) / 60); // convert to km and then divide by average car speed

      return $mins ? $mins : 3; // if is less than a min show at least 3 mins
    }
  }

  return 10;
}

function sendDriverRequest($trip) {
  dispatch(new \App\Modules\Trip\Jobs\RequestNextDriver($trip));
}
function s3resource($file): string {
  return s3('resources/'.$file);
}
function distance(\Grimzy\LaravelMysqlSpatial\Types\Point $point1, \Grimzy\LaravelMysqlSpatial\Types\Point $point2): int {
  $distance = Driver::selectRaw('st_distance_sphere(ST_GeomFromText(\'POINT('.$point1.')\', 4326, \'axis-order=long-lat\'), ST_GeomFromText(\'POINT('.$point2.')\', 4326, \'axis-order=long-lat\')) as distance')->get();

  if (count($distance->toArray()))
    $distance = $distance[0]->distance ?? 0;
  else
    $distance = 0;

  return $distance;
}
function getLanguageId($language){
  return [
    'ar' => 1,
    'en' => 2,
    'de' => 3,
  ][$language];
}
