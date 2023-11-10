<?php

use Illuminate\Support\Facades\Route;

// Mobile Route
Route::group([
  'namespace'   => 'App\Modules\Trip\Controllers\Mobile',
  'prefix'      => 'app/user/trips',
  'as'          => 'app.user.trips.',
  'middleware'  => ['auth']
], function () {

  Route::post('contact-driver', [
    'uses' => 'MyTripsController@contactDriver',
    'as' => 'contact-driver'
  ]);

  Route::post('confirm', [
    'uses' => 'UserController@searchForDriver',
    'as' => 'confirm'
  ]);

  Route::post('schedule', [
    'uses' => 'UserController@schedule',
    'as' => 'schedule'
  ]);

  Route::post('cancel', [
    'uses' => 'UserController@cancelTrip',
    'as' => 'cancel'
  ]);

  Route::get('cancel-reasons', [
    'uses' => 'UserController@cancelReasons',
    'as' => 'reasons'
  ]);

  Route::post('rate', [
    'uses' => 'UserController@rateDriver',
    'as' => 'rate'
  ]);

  Route::post('pay/google', [
    'uses' => 'UserController@attachGooglePayPaymentMethod',
    'as' => 'pay-google'
  ]);

  Route::post('pay/clientside-success', [
    'uses' => 'UserController@finishPaymentClientSideInteraction',
    'as' => 'clientside-success'
  ]);


  Route::group(['prefix' => 'calculating', 'as' => 'app.user.trips.calculating',], function () {

    Route::post('by-category', [
      'uses' => 'UserController@calcCarsPriceList',
      'as' => 'cars-selection'
    ]);

    Route::post('pickup', [
      'uses' => 'UserController@calcWithPickup',
      'as' => 'pickup'
    ]);

  });

  Route::group(['prefix' => 'chat', 'as' => 'app.user.trips.chat',], function () {

    Route::post('send-message', [
      'uses' => 'ChatController@sendUserMessage',
      'as' => 'send-user-message'
    ]);

  });

  Route::group(['prefix' => 'taxi-payments', 'as' => 'app.user.trips.taxi-payments',], function () {

    Route::post('request', [
      'uses' => 'UserController@requestRequiredPayment',
      'as' => 'request'
    ]);

    Route::post('google', [
      'uses' => 'UserController@attachGooglePayPaymentMethodForRequiredPayment',
      'as' => 'google'
    ]);

    Route::post('clientside-success', [
      'uses' => 'UserController@finishPaymentClientSideInteractionRequiredPayment',
      'as' => 'clientside-success'
    ]);
  });

  Route::group(['prefix' => 'calls', 'as' => 'app.user.trips.calls',], function () {

    Route::post('access-token', [
      'uses' => 'CallsController@accessTokenUser',
      'as' => 'access-token'
    ]);

    Route::post('make-call', [
      'uses' => 'CallsController@makeCall',
      'as' => 'make-call'
    ]);


    Route::post('place-call', [
      'uses' => 'CallsController@placeCall',
      'as' => 'place-call'
    ]);


  });

  Route::group(['prefix' => 'my-trips', 'as' => 'app.user.trips.my-trips',], function () {
    Route::post('regular', [
      'uses' => 'MyTripsController@regular',
      'as' => 'regular'
    ]);

    Route::post('scheduled', [
      'uses' => 'MyTripsController@scheduled',
      'as' => 'scheduled'
    ]);
  });
});

Route::group([
  'namespace'   => 'App\Modules\Trip\Controllers\Mobile',
  'prefix'      => 'app/driver/trips',
  'as'          => 'app.driver.trips.',
  'middleware'  => ['auth:driver']
], function () {

  Route::post('accept-request', [
    'uses' => 'DriverController@acceptRequest',
    'as' => 'confirm'
  ]);

  Route::post('cancel', [
    'uses' => 'DriverController@cancelTrip',
    'as' => 'cancel'
  ]);

  Route::get('cancel-reasons', [
    'uses' => 'DriverController@cancelReasons',
    'as' => 'reasons'
  ]);

  Route::post('rate', [
    'uses' => 'DriverController@rateUser',
    'as' => 'rate'
  ]);


  Route::group(['prefix' => 'chat', 'as' => 'chat',], function () {

    Route::post('send-message', [
      'uses' => 'ChatController@sendDriverMessage',
      'as' => 'send-user-message'
    ]);

  });

  Route::group(['prefix' => 'calls', 'as' => 'calls',], function () {

    Route::post('access-token', [
      'uses' => 'CallsController@accessTokenDriver',
      'as' => 'access-token'
    ]);

  });
});

Route::group([
  'namespace'   => 'App\Modules\Trip\Controllers\Mobile',
  'prefix' => 'app/trips/calls',
  'as' => 'app.trips.calls',
], function () {
  Route::post('incoming', [
    'uses'  => 'CallsController@incoming',
    'as'    => 'incoming'
  ]);
});


Route::group([
  'namespace'   => 'App\Modules\Trip\Controllers\Mobile',
  'prefix' => 'app/driver/trips',
  'as' => 'app.driver.trips',
], function () {
  Route::post('requests/send', [
    'uses'  => 'DriverController@sendRequest',
    'as'    => 'send-request'
  ]);
});

Route::group([
  'namespace'  => 'App\Modules\Trip\Controllers\Mobile',
  'prefix'     => 'app/driver/trips',
  'as'         => 'app.driver.trips',
  'middleware' => ['auth:driver']
], function () {
  Route::post('requests/reject', [
    'uses'  => 'DriverController@rejectRequest',
    'as'    => 'reject-request'
  ]);

  Route::post('requests/accept', [
    'uses'  => 'DriverController@acceptRequest',
    'as'    => 'accept-request'
  ]);

  Route::post('checkpoints/iam-here', [
    'uses'  => 'DriverController@sendIamHereMessage',
    'as'    => 'send-iam-here'
  ]);

  Route::post('checkpoints/remaining-time', [
    'uses'  => 'DriverController@remainingTime',
    'as'    => 'remaining-time'
  ]);

  Route::post('checkpoints/start', [
    'uses'  => 'DriverController@startTrip',
    'as'    => 'start-trip'
  ]);

  Route::post('checkpoints/reach-stop', [
    'uses'  => 'DriverController@reachStop',
    'as'    => 'reach-stop'
  ]);

  Route::post('checkpoints/end', [
    'uses'  => 'DriverController@endTrip',
    'as'    => 'end-trip'
  ]);

  Route::post('checkpoints/payment-done', [
    'uses'  => 'DriverController@paymentDone',
    'as'    => 'payment-done'
  ]);

  Route::post('checkpoints/taxi-payment', [
    'uses'  => 'DriverController@requireTaxiPayment',
    'as'    => 'taxi-payment'
  ]);
});


Route ::group([
  'namespace' => 'App\Modules\Trip\Controllers\Dashboard',
  'prefix'    => 'admin/trips',
  'as'        => 'admin.trips.',
  'middleware' => [
    'auth:admin'
  ]
], function () {
  Route ::get('/', [
    'uses' => 'TripController@index',
    'as' => 'index'
  ]);

  Route ::get('/stats/users', [
    'uses' => 'StatsController@users',
    'as' => 'stats.users'
  ]);

  Route ::post('/stats/profit', [
    'uses' => 'StatsController@profit',
    'as' => 'stats.profit'
  ]);

  Route ::get('/stats/status', [
    'uses' => 'StatsController@status',
    'as' => 'stats.status'
  ]);

  Route ::get('/stats/payments', [
    'uses' => 'StatsController@payments',
    'as' => 'stats.payments'
  ]);

  Route ::group([
    'prefix' => '{id}'
  ], function () {
    Route ::get('/fetch', [
      'uses' => 'TripController@show',
      'as' => 'show'
    ]);

    Route ::get('/invoice', [
      'uses' => 'TripController@createInvoice',
      'as' => 'invoice'
    ]);
  });
});

Route ::group([
  'namespace' => 'App\Modules\Trip\Controllers\Portal',
  'prefix'    => 'portal/trips',
  'as'        => 'portal.trips.',
  'middleware' => [
    'auth:partner'
  ]
], function () {
  Route ::get('/', [
    'uses' => 'TripController@index',
    'as' => 'index'
  ]);

  Route ::get('/stats/users', [
    'uses' => 'StatsController@users',
    'as' => 'stats.users'
  ]);

  Route ::post('/stats/profit', [
    'uses' => 'StatsController@profit',
    'as' => 'stats.profit'
  ]);

  Route ::get('/stats/status', [
    'uses' => 'StatsController@status',
    'as' => 'stats.status'
  ]);

  Route ::get('/stats/payments', [
    'uses' => 'StatsController@payments',
    'as' => 'stats.payments'
  ]);

  Route ::get('history', [
    'uses' => 'TripController@trips',
    'as' => 'trips.history'
  ]);

  Route ::group([
    'prefix' => '{id}'
  ], function () {

    Route ::get('/fetch', [
      'uses' => 'TripController@show',
      'as' => 'show'
    ]);

    Route ::get('/invoice', [
      'uses' => 'TripController@createInvoice',
      'as' => 'create-invoice'
    ]);
  });
});
