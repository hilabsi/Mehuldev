<?php

use Illuminate\Support\Facades\Route;

Route::group(
  [
    'namespace' => 'App\Modules\User\Controllers\Dashboard',
    'prefix'    => 'admin/users',
    'as'        => 'admin.users.',
    'middleware' => [
      'auth:admin'
    ]
  ], function () {

  Route::get('/', ['uses' => 'UserController@index', 'as' => 'index']);

  Route::get('/search', ['uses' => 'UserController@search', 'as' => 'search']);

  Route ::group([
    'prefix' => '{id}'
  ], function () {

    Route ::get('/fetch', [
      'uses' => 'UserController@show',
      'as' => 'show'
    ]);
    Route ::post('/business', [
      'uses' => 'UserController@updateBusiness',
      'as' => 'update-business'
    ]);
    Route ::post('update', [
      'uses' => 'UserController@update',
      'as' => 'update'
    ]);
    Route ::post('payment-methods', [
      'uses' => 'UserController@paymentMethodsStats',
      'as' => 'stats'
    ]);
    Route ::delete('destroy', [
      'uses' => 'UserController@destroy',
      'as' => 'destroy'
    ]);
    Route ::post('restore', [
      'uses' => 'UserController@restore',
      'as' => 'restore'
    ]);
    Route ::post('suspend', [
      'uses' => 'UserController@suspend',
      'as' => 'suspend'
    ]);
    Route ::post('stats', [
      'uses' => 'UserController@stats',
      'as' => 'stats'
    ]);
    Route ::post('trips', [
      'uses' => 'UserController@trips',
      'as' => 'trips'
    ]);
    Route ::post('send-notification', [
      'uses' => 'UserController@sendNotification',
      'as' => 'send-notification'
    ]);
    Route ::get('ratings', [
      'uses' => 'UserController@ratings',
      'as' => 'ratings'
    ]);
    Route ::post('unsuspend', [
      'uses' => 'UserController@unsuspend',
      'as' => 'unsuspend'
    ]);
  });
}
);

// Mobile Route
Route::group([
  'namespace'   => 'App\Modules\User\Controllers\Mobile',
  'prefix'      => 'app/user',
  'as'          => 'app.user.',
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
        'middleware'  => ['auth']
      ]);
    });

    Route::group(['prefix' => 'register', 'as' => 'register.'], function () {

      Route::post('email', [
        'uses'  => 'AuthController@register',
        'as'    => 'register'
      ]);

      Route::post('complete-data', [
        'uses'  => 'AuthController@completeData',
        'as'    => 'complete-data'
      ]);

      Route::post('resend-verification', [
        'uses'        => 'AuthController@resendVerificationCode',
        'as'          => 'resend-verification',
        'middleware'  => ['auth']
      ]);

      Route::post('check-verification', [
        'uses'        => 'AuthController@checkVerificationCode',
        'as'          => 'check-verification',
        'middleware'  => ['auth']
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
        'middleware'  => ['auth']
      ]);

      Route::post('social', [
        'uses'  => 'AuthController@loginBySocialAccount',
        'as'    => 'social'
      ]);
    });


  });

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

    });

    Route::post('update-default', [
      'uses' => 'WalletController@updateDefault',
      'as' => 'update-default'
    ]);

    Route::group(['prefix' => 'coupons'], function () {

      Route::post('add', [
        'uses' => 'CouponController@addCoupon',
        'as' => 'add-coupon'
      ]);

      Route::post('toggle', [
        'uses' => 'CouponController@toggleActive',
        'as' => 'toggle'
      ]);
    });
  });

  Route::group(['prefix' => 'profile', 'middleware' => ['auth']], function () {

    Route::post('update', [
      'uses' => 'UserController@update',
      'as' => 'update'
    ]);

    Route::post('language', [
      'uses' => 'UserController@changeLanguage',
      'as' => 'update-language'
    ]);

    Route::post('device-id', [
      'uses'  => 'UserController@updateDeviceId',
      'as'    => 'update-device-id'
    ]);

    Route::post('referral-code', [
      'uses'  => 'WalletController@setReferralCode',
      'as'    => 'set-referral-code'
    ]);

    Route::post('password', [
      'uses'  => 'UserController@changePassword',
      'as'    => 'password'
    ]);

    Route::post('business', [
      'uses'  => 'UserController@updateBusinessProfile',
      'as'    => 'update-business-profile'
    ]);

    Route::post('places', [
      'uses'  => 'UserController@updatePlace',
      'as'    => 'update-places'
    ]);
  });
});
