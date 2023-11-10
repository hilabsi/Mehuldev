<?php

use Illuminate\Support\Facades\Route;

Route::group([
  'namespace' => 'App\Modules\Settings\Controllers\Dashboard',
  'prefix' => 'admin/settings',
  'as' => 'admin.settings.'
], static function () {

  Route::get('/', [
    'uses' => 'SettingsController@index',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::group([
    'prefix' => 'user-cancel-reasons',
    'as' => 'user-cancel-reasons.'
  ], static function () {
    Route::get('/', [
      'uses' => 'UserCancelReasonController@index',
      'as' => 'index'
    ]);

    Route::post('/', [
      'uses' => 'UserCancelReasonController@store',
      'as' => 'store',
      'middleware' => [
        'auth:admin'
      ]
    ]);

    Route::group([
      'prefix' => '{id}'
    ], static function () {
      Route::get('/fetch', [
        'uses' => 'UserCancelReasonController@show',
        'as' => 'show'
      ]);
      Route::post('update', [
        'uses' => 'UserCancelReasonController@update',
        'as' => 'update',
        'middleware' => [
          'auth:admin'
        ]
      ]);
    });
  });

  Route::group([
    'prefix' => 'driver-cancel-reasons',
    'as' => 'driver-cancel-reasons.'
  ], static function () {
    Route::get('/', [
      'uses' => 'DriverCancelReasonController@index',
      'as' => 'index'
    ]);

    Route::post('/', [
      'uses' => 'DriverCancelReasonController@store',
      'as' => 'store',
      'middleware' => [
        'auth:admin'
      ]
    ]);

    Route::group([
      'prefix' => '{id}'
    ], static function () {
      Route::get('/fetch', [
        'uses' => 'DriverCancelReasonController@show',
        'as' => 'show'
      ]);
      Route::post('update', [
        'uses' => 'DriverCancelReasonController@update',
        'as' => 'update',
        'middleware' => [
          'auth:admin'
        ]
      ]);
    });
  });

  Route::group([
    'prefix' => 'car-brands',
    'as' => 'car-brands.'
  ], static function () {
    Route::get('/', [
      'uses' => 'CarBrandController@index',
      'as' => 'index'
    ]);

    Route::post('/', [
      'uses' => 'CarBrandController@store',
      'as' => 'store',
      'middleware' => [
        'auth:admin'
      ]
    ]);

    Route::group([
      'prefix' => '{id}'
    ], function () {
      Route::get('/fetch', [
        'uses' => 'CarBrandController@show',
        'as' => 'show'
      ]);
      Route::post('update', [
        'uses' => 'CarBrandController@update',
        'as' => 'update',
        'middleware' => [
          'auth:admin'
        ]
      ]);
    });
  });
  Route::group([
    'prefix' => 'car-documents',
    'as' => 'car-documents.'
  ], static function () {
    Route::get('/', [
      'uses' => 'CarDocumentController@index',
      'as' => 'index'
    ]);

    Route::post('/', [
      'uses' => 'CarDocumentController@store',
      'as' => 'store',
      'middleware' => [
        'auth:admin'
      ]
    ]);

    Route::group([
      'prefix' => '{id}'
    ], function () {
      Route::get('/fetch', [
        'uses' => 'CarDocumentController@show',
        'as' => 'show'
      ]);
      Route::post('update', [
        'uses' => 'CarDocumentController@update',
        'as' => 'update',
        'middleware' => [
          'auth:admin'
        ]
      ]);
      Route::post('destroy', [
        'uses' => 'CarDocumentController@destroy',
        'as' => 'destroy',
        'middleware' => [
          'auth:admin'
        ]
      ]);
    });
  });
  Route::group([
    'prefix' => 'driver-documents',
    'as' => 'driver-documents.'
  ], static function () {
    Route::get('/', [
      'uses' => 'DriverDocumentController@index',
      'as' => 'index'
    ]);

    Route::post('/', [
      'uses' => 'DriverDocumentController@store',
      'as' => 'store',
      'middleware' => [
        'auth:admin'
      ]
    ]);

    Route::group([
      'prefix' => '{id}'
    ], static function () {
      Route::get('/fetch', [
        'uses' => 'DriverDocumentController@show',
        'as' => 'show'
      ]);
      Route::post('update', [
        'uses' => 'DriverDocumentController@update',
        'as' => 'update',
        'middleware' => [
          'auth:admin'
        ]
      ]);

      Route::post('destroy', [
        'uses' => 'DriverDocumentController@destroy',
        'as' => 'destroy',
        'middleware' => [
          'auth:admin'
        ]
      ]);
    });
  });
  Route::group([
    'prefix' => 'partner-documents',
    'as' => 'partner-documents.',
  ], static function () {
    Route::get('/', [
      'uses' => 'PartnerDocumentController@index',
      'as' => 'index'
    ]);

    Route::post('/', [
      'uses' => 'PartnerDocumentController@store',
      'as' => 'store',
      'middleware' => [
        'auth:admin'
      ]
    ]);

    Route::group([
      'prefix' => '{id}'
    ], static function () {
      Route::get('/fetch', [
        'uses' => 'PartnerDocumentController@show',
        'as' => 'show'
      ]);
      Route::post('update', [
        'uses' => 'PartnerDocumentController@update',
        'as' => 'update',
        'middleware' => [
          'auth:admin'
        ]
      ]);

      Route::post('destroy', [
        'uses' => 'PartnerDocumentController@destroy',
        'as' => 'destroy',
        'middleware' => [
          'auth:admin'
        ]
      ]);
    });
  });

  Route::group([
    'prefix' => 'car-models',
    'as' => 'car-models.'
  ], static function () {
    Route::get('/', [
      'uses' => 'CarModelController@index',
      'as' => 'index'
    ]);

    Route::post('/', [
      'uses' => 'CarModelController@store',
      'as' => 'store',
      'middleware' => [
        'auth:admin'
      ]
    ]);

    Route::group([
      'prefix' => '{id}'
    ], static function () {
      Route::get('/fetch', [
        'uses' => 'CarModelController@show',
        'as' => 'show'
      ]);
      Route::post('update', [
        'uses' => 'CarModelController@update',
        'as' => 'update',
        'middleware' => [
          'auth:admin'
        ]
      ]);
    });
  });

  Route::get('general', [
    'uses' => 'SettingsController@general',
    'middleware' => [
      'auth:admin'
    ]
  ]);
  Route::get('advanced', [
    'uses' => 'SettingsController@advanced',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::post('general/update', [
    'uses' => 'SettingsController@updateGeneral',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::post('/', [
    'uses' => 'SettingsController@store',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::get('license-types', [
    'uses' => 'LicenseTypeController@index'
  ]);
  Route::get('car-models', [
    'uses' => 'CarModelController@index'
  ]);

//  Route ::get('{id}', [
//    'uses' => 'SettingsController@show'
//  ]);

  Route::post('{id}/update', [
    'uses' => 'SettingsController@update',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::get('logo', [
    'uses' => 'SettingsController@logo'
  ]);

  Route::get('large-logo', [
    'uses' => 'SettingsController@largeLogo'
  ]);
});

Route::group([
  'namespace' => 'App\Modules\Settings\Controllers\Mobile',
  'prefix' => 'app/configurations/slider',
  'as' => 'app.configurations.slider.',
  'middleware' => []
], function () {
  Route::get('/', [
    'uses' => 'OnBoardingSlideController@index'
  ]);
});
