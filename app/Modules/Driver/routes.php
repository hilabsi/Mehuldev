<?php

use Illuminate\Support\Facades\Route;

Route ::group([
  'namespace' => 'App\Modules\Driver\Controllers\Dashboard',
  'prefix'    => 'admin/drivers',
  'as'        => 'admin.drivers.',
  'middleware' => [
    'auth:admin'
  ]
], function () {
  Route ::get('/', [
    'uses' => 'DriverController@index',
    'as' => 'index'
  ]);

  Route ::get('/search', [
    'uses' => 'DriverController@search',
    'as' => 'search'
  ]);

  Route ::post('/', [
    'uses' => 'DriverController@store',
    'as' => 'store'
  ]);

  Route ::group([
    'prefix' => '{id}'
  ], function () {
    Route ::get('/fetch', [
      'uses' => 'DriverController@show',
      'as' => 'show'
    ]);
    Route ::post('update', [
      'uses' => 'DriverController@update',
      'as' => 'update'
    ]);
    Route ::post('payment-methods', [
      'uses' => 'DriverController@paymentMethodsStats',
      'as' => 'stats'
    ]);
    Route ::delete('destroy', [
      'uses' => 'DriverController@destroy',
      'as' => 'destroy'
    ]);
    Route ::post('restore', [
      'uses' => 'DriverController@restore',
      'as' => 'restore'
    ]);
    Route ::post('suspend', [
      'uses' => 'DriverController@suspend',
      'as' => 'suspend'
    ]);
    Route ::post('stats', [
      'uses' => 'DriverController@stats',
      'as' => 'stats'
    ]);
    Route ::post('trips', [
      'uses' => 'DriverController@trips',
      'as' => 'trips'
    ]);
    Route ::post('send-notification', [
      'uses' => 'DriverController@sendNotification',
      'as' => 'send-notification'
    ]);
    Route ::get('ratings', [
      'uses' => 'DriverController@ratings',
      'as' => 'ratings'
    ]);
    Route ::post('unsuspend', [
      'uses' => 'DriverController@unsuspend',
      'as' => 'unsuspend'
    ]);
  });
});

Route::group([
  'namespace'   => 'App\Modules\Driver\Controllers\Mobile',
  'prefix'      => 'app/driver',
  'as'          => 'app.driver.',
  'middleware'  => []
], function () {

  Route::group(['prefix' => 'auth'], function () {

    Route::group(['prefix' => 'password-resets', 'as' => 'password-resets.'], function () {

      Route::post('request-code', [
        'uses'  => 'AuthController@forgotPassword',
        'as'    => 'request-code'
      ]);

      Route::post('check-code', [
        'uses'        => 'AuthController@checkResetPasswordCode',
        'as'          => 'check-code',
      ]);

      Route::post('reset', [
        'uses'  => 'AuthController@resetPassword',
        'as'    => 'reset',
        'middleware' => ['auth:driver']
      ]);
    });

    Route::group(['prefix' => 'login', 'as' => 'login.'], function () {

      Route::post('email', [
        'uses'  => 'AuthController@loginByEmail',
        'as'    => 'email'
      ]);

      Route::post('refresh', [
        'uses'        => 'AuthController@refreshToken',
        'as'          => 'refresh',
        'middleware'  => ['auth:driver']
      ]);
    });

  });

  Route::group(['prefix' => 'income', 'middleware' => ['auth:driver']], function () {

    Route::post('calc/by-week', [
      'uses' => 'DriverController@calcByWeek',
      'as' => 'income.by-week'
    ]);

    Route::post('calc/by-day', [
      'uses' => 'DriverController@calcByDay',
      'as' => 'income.by-day'
    ]);

    Route::post('trips/by-day', [
      'uses' => 'DriverController@tripsByDay',
      'as' => 'income.by-day.trips'
    ]);

    Route::post('trips/by-week', [
      'uses' => 'DriverController@tripsByWeek',
      'as' => 'income.by-week.trips'
    ]);
  });

  Route::group(['prefix' => 'notifications', 'middleware' => ['auth:driver']], function () {

    Route::get('/', [
      'uses' => 'DriverController@notifications',
      'as' => 'notifications.index'
    ]);

    Route::post('read', [
      'uses' => 'DriverController@markAsRead',
      'as' => 'notifications.mark-as-read'
    ]);

    Route::post('create', [
      'uses' => 'DriverController@createNotification',
      'as' => 'notifications.create'
    ]);

    Route::post('delete', [
      'uses' => 'DriverController@deleteNotification',
      'as' => 'notifications.delete'
    ]);
  });

  Route::group(['prefix' => 'profile', 'middleware' => ['auth:driver']], function () {

    Route::post('skip-rating', [
      'uses' => 'DriverController@skipRating',
      'as' => 'skip-rating'
    ]);

    Route::post('update', [
      'uses' => 'DriverController@update',
      'as' => 'update'
    ]);

    Route::post('language', [
      'uses' => 'DriverController@changeLanguage',
      'as' => 'update-language'
    ]);

    Route::post('location', [
      'uses' => 'DriverController@updateLocation',
      'as' => 'update-location'
    ]);

    Route::post('device-id', [
      'uses'  => 'DriverController@updateDeviceId',
      'as'    => 'update-device-id'
    ]);

    Route::post('password', [
      'uses'  => 'DriverController@changePassword',
      'as'    => 'password'
    ]);
  });
});


Route ::group([
  'namespace' => 'App\Modules\Driver\Controllers\Portal',
  'prefix'    => 'portal/drivers',
  'as'        => 'portal.drivers.',
  'middleware' => [
    'auth:partner'
  ]
], function () {
  Route ::get('/', [
    'uses' => 'DriverController@index',
    'as' => 'index'
  ]);

  Route ::get('/search', [
    'uses' => 'DriverController@search',
    'as' => 'search'
  ]);

  Route ::post('/', [
    'uses' => 'DriverController@store',
    'as' => 'store'
  ]);

  Route ::get('reports', [
    'uses' => 'DriverController@reports',
    'as' => 'reports'
  ]);

  Route ::group([
    'prefix' => '{id}'
  ], function () {
    Route ::get('/fetch', [
      'uses' => 'DriverController@show',
      'as' => 'show'
    ]);
    Route ::post('update', [
      'uses' => 'DriverController@update',
      'as' => 'update'
    ]);
    Route ::post('payment-methods', [
      'uses' => 'DriverController@paymentMethodsStats',
      'as' => 'stats'
    ]);
    Route ::delete('destroy', [
      'uses' => 'DriverController@destroy',
      'as' => 'destroy'
    ]);
    Route ::post('restore', [
      'uses' => 'DriverController@restore',
      'as' => 'restore'
    ]);
    Route ::post('suspend', [
      'uses' => 'DriverController@suspend',
      'as' => 'suspend'
    ]);
    Route ::post('stats', [
      'uses' => 'DriverController@stats',
      'as' => 'stats'
    ]);
    Route ::post('trips', [
      'uses' => 'DriverController@trips',
      'as' => 'trips'
    ]);
    Route ::post('send-notification', [
      'uses' => 'DriverController@sendNotification',
      'as' => 'send-notification'
    ]);
    Route ::get('ratings', [
      'uses' => 'DriverController@ratings',
      'as' => 'ratings'
    ]);
    Route ::post('unsuspend', [
      'uses' => 'DriverController@unsuspend',
      'as' => 'unsuspend'
    ]);
  });
});
