<?php

use Illuminate\Support\Facades\Route;

Route ::group([
  'namespace' => 'App\Modules\Invoice\Controllers\Dashboard',
  'prefix' => 'admin/invoices',
  'as' => 'admin.invoices.',
  'middleware' => [
    'auth:admin'
  ]
], function () {

  Route ::post('/', [
    'uses' => 'InvoiceController@index',
    'as' => 'index'
  ]);

  Route ::post('/partners/{id}', [
    'uses' => 'InvoiceController@partners',
    'as' => 'partners'
  ]);

  Route ::get('search', [
    'uses' => 'InvoiceController@search',
    'as' => 'search'
  ]);

  Route ::group(['prefix' => '{id}'], function () {

    Route ::get('fetch', [
      'uses' => 'InvoiceController@show',
      'as' => 'show'
    ]);
    Route ::post('update', [
      'uses' => 'InvoiceController@update',
      'as' => 'update'
    ]);
  });
});

Route ::group([
  'namespace'   => 'App\Modules\Invoice\Controllers\Portal',
  'prefix'      => 'portal/invoices',
  'as'          => 'portal.invoices.',
  'middleware' => [
    'auth:partner'
  ]
], function () {

  Route ::get('/', [
    'uses' => 'InvoiceController@index',
    'as' => 'index'
  ]);

  Route ::get('{id}/invoice', [
    'uses' => 'InvoiceController@show',
    'as' => 'show'
  ]);

  Route ::post('{id}/update', [
    'uses' => 'InvoiceController@update',
    'as' => 'update'
  ]);
});
