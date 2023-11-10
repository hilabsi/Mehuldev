<?php

use Illuminate\Support\Facades\Route;

Route ::group([
  'namespace' => 'App\Modules\SmsTemplate\Controllers\Dashboard',
  'prefix' => 'admin/settings/sms-templates',
  'as' => 'admin.settings.sms-templates.',
  'middleware' => [
    'auth:admin'
  ]
], function () {
  Route ::get('/', [
    'uses' => 'SmsTemplateController@index'
  ]);

  Route ::post('/', [
    'uses' => 'SmsTemplateController@store'
  ]);

  Route ::get('{id}/fetch', [
    'uses' => 'SmsTemplateController@show'
  ]);

  Route ::post('{id}/update', [
    'uses' => 'SmsTemplateController@update'
  ]);
});
