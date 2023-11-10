<?php

use Illuminate\Support\Facades\Route;

Route ::group([
  'namespace'   => 'App\Modules\Support\Controllers\Dashboard',
  'prefix'      => 'admin/support',
  'as'          => 'admin.support.',
  'middleware' => [
    'auth:admin'
  ]
], function () {

  Route ::get('missing-requests/', [
    'uses'  => 'MissingRequestController@index',
    'as'    => 'missing-requests.index',
  ]);

  Route ::post('missing-requests/{id}/update', [
    'uses'  => 'MissingRequestController@update',
    'as'    => 'missing-requests.update',
  ]);

  Route ::group([
    'prefix'    => 'categories',
    'as'        => 'categories.',
  ], function () {
    Route ::get('/', [
      'uses' => 'SupportCategoryController@index',
      'as' => 'index'
    ]);

    Route ::post('/', [
      'uses' => 'SupportCategoryController@store',
      'as' => 'store'
    ]);

    Route ::group([
      'prefix' => '{id}'
    ], function () {
      Route ::get('/fetch', [
        'uses' => 'SupportCategoryController@show',
        'as' => 'show'
      ]);
      Route ::post('update', [
        'uses' => 'SupportCategoryController@update',
        'as' => 'update'
      ]);
      Route ::delete('destroy', [
        'uses' => 'SupportCategoryController@destroy',
        'as' => 'destroy'
      ]);
    });
  });


  Route ::group([
    'prefix'    => 'questions',
    'as'        => 'questions.',
  ], function () {
    Route ::get('{id}', [
      'uses' => 'SupportCategoryQuestionController@index',
      'as' => 'index'
    ]);

    Route ::post('/', [
      'uses' => 'SupportCategoryQuestionController@store',
      'as' => 'store'
    ]);

    Route ::group([
      'prefix' => '{id}'
    ], function () {
      Route ::get('/fetch', [
        'uses' => 'SupportCategoryQuestionController@show',
        'as' => 'show'
      ]);
      Route ::post('update', [
        'uses' => 'SupportCategoryQuestionController@update',
        'as' => 'update'
      ]);
      Route ::delete('destroy', [
        'uses' => 'SupportCategoryQuestionController@destroy',
        'as' => 'destroy'
      ]);
    });
  });

});

Route ::group([
  'namespace'   => 'App\Modules\Support\Controllers\Mobile',
  'prefix'      => 'app/user/support',
  'as'          => 'app.user.support.',
  'middleware'  => [],
], function () {

  Route ::get('/', [
    'uses'  => 'UserController@categories',
    'as'    => 'categories',
  ]);

  Route ::post('send-message', [
    'uses'  => 'UserController@sendMessage',
    'as'    => 'send-message'
  ]);

  Route ::post('request-call', [
    'uses'  => 'CallsController@accessToken',
    'as'    => 'access-token'
  ]);

  Route ::post('calls/incoming', [
    'uses'  => 'CallsController@incoming',
    'as'    => 'incoming'
  ]);

  Route ::get('{id}/questions', [
    'uses'  => 'UserController@questions',
    'as'    => 'questions'
  ]);
});

Route ::group([
  'namespace'   => 'App\Modules\Support\Controllers\Mobile',
  'prefix'      => 'app/driver/support',
  'as'          => 'app.driver.support.',
  'middleware'  => [],
], function () {

  Route ::get('/', [
    'uses'  => 'DriverController@categories',
    'as'    => 'categories',
  ]);

  Route ::post('send-message', [
    'uses'  => 'DriverController@sendMessage',
    'as'    => 'send-message'
  ]);

  Route ::get('{id}/questions', [
    'uses'  => 'DriverController@questions',
    'as'    => 'questions'
  ]);
});
