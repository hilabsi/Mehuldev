<?php

use Illuminate\Support\Facades\Route;

Route ::group([
  'namespace' => 'App\Modules\Campaign\Controllers\Dashboard',
  'prefix' => 'admin/campaigns',
  'as' => 'admin.campaigns.',
  'middleware' => [
    'auth:admin'
  ]
], function () {

  Route ::get('/', [
    'uses' => 'CampaignController@index'
  ]);

  Route ::post('/', [
    'uses' => 'CampaignController@store'
  ]);

  Route ::group([
    'prefix' => 'sms-templates',
    'as' => 'sms-templates.',
  ], function () {
    Route ::get('/', [
      'uses' => 'SMSTemplateController@index'
    ]);

    Route ::post('/', [
      'uses' => 'SMSTemplateController@store'
    ]);

    Route ::get('{id}/fetch', [
      'uses' => 'SMSTemplateController@show'
    ]);

    Route ::post('{id}/update', [
      'uses' => 'SMSTemplateController@update'
    ]);

    Route ::post('{id}/destroy', [
      'uses' => 'SMSTemplateController@destroy'
    ]);
  });

  Route ::group([
    'prefix' => 'email-templates',
    'as' => 'email-templates.',
  ], function () {
    Route ::get('/', [
      'uses' => 'EmailTemplateController@index'
    ]);

    Route ::post('/', [
      'uses' => 'EmailTemplateController@store'
    ]);

    Route ::get('{id}/fetch', [
      'uses' => 'EmailTemplateController@show'
    ]);

    Route ::post('{id}/update', [
      'uses' => 'EmailTemplateController@update'
    ]);

    Route ::post('{id}/destroy', [
      'uses' => 'EmailTemplateController@destroy'
    ]);
  });

  Route ::get('{id}/fetch', [
    'uses' => 'CampaignController@show'
  ]);

  Route ::post('{id}/send', [
    'uses' => 'CampaignController@send'
  ]);

  Route ::post('{id}/update', [
    'uses' => 'CampaignController@update'
  ]);
});
