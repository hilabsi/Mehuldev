<?php

use Illuminate\Support\Facades\Route;

Route::group([
  'namespace' => 'App\Modules\Language\Controllers\Dashboard',
  'prefix' => 'admin/languages',
  'as' => 'admin.languages.'
], static function () {
  Route::get('/', [
    'uses' => 'LanguageController@index'
  ]);

  Route::post('/', [
    'uses' => 'LanguageController@store',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::get('{id}', [
    'uses' => 'LanguageController@show'
  ]);

  Route::post('{id}/update', [
    'uses' => 'LanguageController@update',
    'middleware' => [
      'auth:admin'
    ]
  ]);
});

Route::group([
  'namespace' => 'App\Modules\Language\Controllers\Mobile',
  'prefix' => 'app/configurations/languages',
  'as' => 'app.configurations.languages.',
  'middleware' => [

  ]
], static function () {

  Route::get('/', [
    'uses' => 'LanguageController@index',
    'as' => 'index'
  ]);
});
