<?php

use Illuminate\Support\Facades\Route;

Route::group([
  'namespace' => 'App\Modules\Country\Controllers\Dashboard',
  'prefix' => 'admin/countries',
  'as' => 'admin.countries.'
], static function () {
  Route::get('/', [
    'uses' => 'CountryController@index',
    'as' => 'index'
  ]);

  Route::post('/', [
    'uses' => 'CountryController@store',
    'as' => 'store',
    'middleware' => [
      'auth:admin'
    ]

  ]);
  Route::get('{id}', [
    'uses' => 'CountryController@show',
    'as' => 'show'
  ]);

  Route::post('{id}/update', [
    'uses' => 'CountryController@update',
    'as' => 'update',
    'middleware' => [
      'auth:admin'
    ]
  ]);
});

Route::group([
  'namespace' => 'App\Modules\Country\Controllers\Dashboard',
  'prefix' => 'admin/cities',
  'as' => 'admin.cities.'
], static function () {
  Route::get('/', [
    'uses' => 'CityController@index',
    'as' => 'index'
  ]);

  Route::post('/', [
    'uses' => 'CityController@store',
    'as' => 'store',
    'middleware' => [
      'auth:admin'
    ]
  ]);
  Route::get('{id}/fetch', [
    'uses' => 'CityController@show',
    'as' => 'show'
  ]);

  Route::post('{id}/update', [
    'uses' => 'CityController@update',
    'as' => 'update',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::post('{id}/destroy', [
    'uses' => 'CityController@destroy',
    'as' => 'destroy',
    'middleware' => [
      'auth:admin'
    ]
  ]);
});

Route::group([
  'namespace' => 'App\Modules\Country\Controllers\Mobile',
  'prefix' => 'app/configurations/countries',
  'as' => 'app.configurations.countries.',
  'middleware' => [

  ]
], static function () {

  Route::get('/', [
    'uses' => 'CountryController@index',
    'as' => 'index'
  ]);
});
