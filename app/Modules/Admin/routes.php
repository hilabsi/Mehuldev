<?php

use Illuminate\Support\Facades\Route;

Route ::group([
  'namespace' => 'App\Modules\Admin\Controllers\Dashboard',
  'prefix' => 'admin/admins',
  'as' => 'admin.admins.',
], function () {

  Route::group(['prefix' => 'auth'], function () {

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

  Route::group(['middleware' => ['auth:admin']], function () {

    Route ::get('/', [
      'uses' => 'AdminController@index',
      'as' => 'index'
    ]);

    Route ::get('search', [
      'uses' => 'AdminController@search',
      'as' => 'search'
    ]);

    Route ::post('/', [
      'uses' => 'AdminController@store',
      'as' => 'store'
    ]);

    Route ::group(['prefix' => '{id}'], function () {

      Route ::get('fetch', [
        'uses' => 'AdminController@show',
        'as' => 'show'
      ]);
      Route ::post('update', [
        'uses' => 'AdminController@update',
        'as' => 'update'
      ]);
      Route ::post('disable', [
        'uses' => 'AdminController@disable',
        'as' => 'disable'
      ]);
      Route ::post('activate', [
        'uses' => 'AdminController@activate',
        'as' => 'activate'
      ]);
    });
  });
});

Route ::group([
  'namespace' => 'App\Modules\Admin\Controllers\Dashboard',
  'prefix' => 'admin/admin-roles',
  'as' => 'admin.admin-roles.',
  'middleware' => [
    'auth:admin'
  ]
], function () {
  Route ::get('/', [
    'uses' => 'AdminRoleController@index',
    'as' => 'index'
  ]);

  Route ::post('/', [
    'uses' => 'AdminRoleController@store',
    'as' => 'store'
  ]);

  Route ::get('{id}', [
    'uses' => 'AdminRoleController@show',
    'as' => 'show'
  ]);

  Route ::post('{id}/update', [
    'uses' => 'AdminRoleController@update',
    'as' => 'update'
  ]);
  
  Route ::delete('{id}/destroy', [
    'uses' => 'AdminRoleController@destroy',
    'as' => 'destroy'
    ]);
});


Route ::group([
  'namespace' => 'App\Modules\Admin\Controllers\Dashboard',
  'prefix' => 'admin/admin-modules',
  'as' => 'admin.admin-modules.',
  'middleware' => [
    'auth:admin'
  ]
], function () {
  Route ::get('/', [
    'uses' => 'AdminPermissionModuleController@index',
    'as' => 'index'
  ]);
});

