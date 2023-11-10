<?php

use Illuminate\Support\Facades\Route;

Route ::group([
                'namespace' => 'App\Modules\EmailTemplate\Controllers\Dashboard',
                'prefix' => 'admin/settings/email-templates',
                'as' => 'admin.settings.email-templates.',
                'middleware' => [
                  'auth:admin'
                ]
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
});
