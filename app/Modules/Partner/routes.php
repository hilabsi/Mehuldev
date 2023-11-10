<?php

use Illuminate\Support\Facades\Route;

Route::group([
  'namespace' => 'App\Modules\Partner\Controllers\Dashboard',
  'prefix' => 'portal/auth',
  'as' => 'portal.auth',
  'middleware' => []
], function () {

  Route::post('register', [
    'uses'  => 'AuthController@register',
    'as'    => 'register'
  ]);

  Route::post('verify', [
    'uses'  => 'AuthController@verify',
    'as'    => 'verify'
  ]);

  Route::group(['prefix' => 'login', 'as' => 'login.'], function () {

    Route::post('email', [
      'uses'  => 'AuthController@loginByEmail',
      'as'    => 'email'
    ]);

    Route::post('refresh', [
      'uses'        => 'AuthController@refreshToken',
      'as'          => 'refresh',
      'middleware'  => ['auth']
    ]);

  });
});

Route ::group([
  'namespace' => 'App\Modules\Partner\Controllers\Dashboard',
  'prefix' => 'admin/partners',
  'as' => 'admin.partners.',
  'middleware' => [
    'auth:admin'
  ]
], function () {

  Route ::get('/', [
    'uses'  => 'PartnerController@index',
    'as'    => 'index'
  ]);

  Route ::get('search', [
    'uses' => 'PartnerController@search',
    'as' => 'search'
  ]);

  Route ::post('/', [
    'uses' => 'PartnerController@store',
    'as' => 'store'
  ]);

  Route ::group(['prefix' => '{id}'], function () {

    Route ::get('fetch', [
      'uses' => 'PartnerController@show',
      'as' => 'show'
    ]);
    Route ::post('update', [
      'uses' => 'PartnerController@update',
      'as' => 'update'
    ]);
    Route ::post('destroy', [
      'uses' => 'PartnerController@destroy',
      'as' => 'destroy'
    ]);
    Route ::post('/activate', [
      'uses' => 'PartnerController@activate',
      'as' => 'restore'
    ]);
  });
});

Route ::group([
  'namespace' => 'App\Modules\Partner\Controllers\Dashboard',
  'prefix'    => 'portal/trips',
  'as'        => 'portal.trips.',
  'middleware' => [
    'auth:partner'
  ]
], function () {

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

});
