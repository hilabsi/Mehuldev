<?php

use Illuminate\Support\Facades\Route;

Route ::group([
  'namespace' => 'App\Modules\PaymentMethod\Controllers\Dashboard',
  'prefix' => 'admin/payment-methods',
  'as' => 'admin.payment-methods.',
  'middleware' => [
    'auth:admin'
  ]
], function () {

  Route ::get('/', [
    'uses' => 'PaymentMethodController@index'
  ]);

  Route ::post('/', [
    'uses' => 'PaymentMethodController@store'
  ]);

  Route ::get('{id}', [
    'uses' => 'PaymentMethodController@show'
  ]);

  Route ::post('{id}/update', [
    'uses' => 'PaymentMethodController@update'
  ]);
});


// Mobile Route
Route::group([
  'namespace'   => 'App\Modules\PaymentMethod\Controllers\Mobile',
  'prefix'      => 'app/payments',
  'as'          => 'app.payments.',
  'middleware'  => []
], function () {

  Route::group(['prefix' => 'wallet/{walletType}', 'middleware' => ['auth']], function () {

    Route::group(['prefix' => 'cards'], function () {

      Route::post('add', [
        'uses' => 'WalletController@addCard',
        'as' => 'add-card'
      ]);

      Route::post('remove', [
        'uses' => 'WalletController@removeCard',
        'as' => 'remove-card'
      ]);

      Route::post('set-default', [
        'uses' => 'WalletController@setDefault',
        'as' => 'set-default'
      ]);
    });
  });
});
