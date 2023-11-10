<?php

use Illuminate\Support\Facades\Route;

Route ::group([
                'namespace' => 'App\Modules\Coupon\Controllers\Dashboard',
                'prefix' => 'admin/coupons',
                'as' => 'admin.coupons.',
                'middleware' => [
                  'auth:admin'
                ]
              ], function () {

  Route ::get('/', [
    'uses' => 'CouponController@index'
  ]);

  Route ::post('/', [
    'uses' => 'CouponController@store'
  ]);

  Route ::get('{id}/fetch', [
    'uses' => 'CouponController@show'
  ]);

  Route ::post('{id}/update', [
    'uses' => 'CouponController@update'
  ]);
});
