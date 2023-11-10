<?php

use Illuminate\Support\Facades\Route;

Route ::group([
                'namespace' => 'App\Modules\CustomPage\Controllers\Dashboard',
                'prefix' => 'admin/custom-pages',
                'as' => 'admin.custom-pages.',
                'middleware' => [
                  'auth:admin'
                ]
              ], function () {

  Route ::get('/', [
    'uses' => 'CustomPageController@index'
  ]);

  Route ::post('/', [
    'uses' => 'CustomPageController@store'
  ]);

  Route ::get('{id}/fetch', [
    'uses' => 'CustomPageController@show'
  ]);

  Route ::post('{id}/update', [
    'uses' => 'CustomPageController@update'
  ]);
});

Route ::group([
                'namespace' => 'App\Modules\CustomPage\Controllers\Mobile',
                'prefix' => 'app/user/pages',
                'as' => 'app.user.pages.',
                'middleware' => []
              ], function () {

  Route ::post('/', [
    'uses' => 'CustomPageController@index'
  ]);
});

Route ::group([
                'namespace' => 'App\Modules\CustomPage\Controllers\Mobile',
                'prefix' => 'app/driver/pages',
                'as' => 'app.driver.pages.',
                'middleware' => []
              ], function () {

  Route ::post('/', [
    'uses' => 'CustomPageController@index'
  ]);
});
