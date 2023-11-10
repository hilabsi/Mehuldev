<?php

use Illuminate\Support\Facades\Route;

Route::group([
  'namespace' => 'App\Modules\Car\Controllers\Dashboard',
  'prefix' => 'admin/cars',
  'as' => 'admin.cars.'
], function () {
  Route::get('/', [
    'uses' => 'CarController@index',
    'as' => 'index'
  ]);

  Route::get('/search', [
    'uses' => 'CarController@search',
    'as' => 'search'
  ]);

  Route::post('/', [
    'uses' => 'CarController@store',
    'as' => 'store',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::group([
    'prefix' => '{id}'
  ], function () {
    Route::get('/fetch', [
      'uses' => 'CarController@show',
      'as' => 'show'
    ]);
    Route::post('payment-methods', [
      'uses' => 'CarController@paymentMethodsStats',
      'as' => 'stats'
    ]);
    Route::post('update', [
      'uses' => 'CarController@update',
      'as' => 'update',
      'middleware' => [
        'auth:admin'
      ]
    ]);
    Route::delete('destroy', [
      'uses' => 'CarController@destroy',
      'as' => 'destroy',
      'middleware' => [
        'auth:admin'
      ]
    ]);
    Route::post('restore', [
      'uses' => 'CarController@restore',
      'as' => 'restore',
      'middleware' => [
        'auth:admin'
      ]
    ]);
    Route::post('stats', [
      'uses' => 'CarController@stats',
      'as' => 'stats',
      'middleware' => [
        'auth:admin'
      ]
    ]);
    Route::post('trips', [
      'uses' => 'CarController@trips',
      'as' => 'trips',
      'middleware' => [
        'auth:admin'
      ]
    ]);
    Route::post('suspend', [
      'uses' => 'CarController@suspend',
      'as' => 'suspend',
      'middleware' => [
        'auth:admin'
      ]
    ]);
    Route::post('unsuspend', [
      'uses' => 'CarController@unsuspend',
      'as' => 'unsuspend',
      'middleware' => [
        'auth:admin'
      ]
    ]);
  });
});
Route::group([
  'namespace' => 'App\Modules\Car\Controllers\Portal',
  'prefix' => 'portal/cars',
  'as' => 'portal.cars.',
  'middleware' => [
    'auth:partner'
  ]
], function () {
  Route::get('/', [
    'uses' => 'CarController@index',
    'as' => 'index'
  ]);

  Route::get('/search', [
    'uses' => 'CarController@search',
    'as' => 'search'
  ]);

  Route::post('/', [
    'uses' => 'CarController@store',
    'as' => 'store'
  ]);

  Route::group([
    'prefix' => '{id}'
  ], function () {
    Route::get('/fetch', [
      'uses' => 'CarController@show',
      'as' => 'show'
    ]);
    Route::post('payment-methods', [
      'uses' => 'CarController@paymentMethodsStats',
      'as' => 'stats'
    ]);
    Route::post('update', [
      'uses' => 'CarController@update',
      'as' => 'update'
    ]);
    Route::delete('destroy', [
      'uses' => 'CarController@destroy',
      'as' => 'destroy'
    ]);
    Route::post('restore', [
      'uses' => 'CarController@restore',
      'as' => 'restore'
    ]);
    Route::post('stats', [
      'uses' => 'CarController@stats',
      'as' => 'stats'
    ]);
    Route::post('trips', [
      'uses' => 'CarController@trips',
      'as' => 'trips'
    ]);
    Route::post('suspend', [
      'uses' => 'CarController@suspend',
      'as' => 'suspend'
    ]);
    Route::post('unsuspend', [
      'uses' => 'CarController@unsuspend',
      'as' => 'unsuspend'
    ]);
  });
});

Route::group([
  'namespace' => 'App\Modules\Car\Controllers\Dashboard',
  'prefix' => 'admin/cars/categories',
  'as' => 'admin.cars.categories.'
], function () {
  Route::get('/', [
    'uses' => 'CarCategoryController@index',
    'as' => 'index'
  ]);

  Route::post('/', [
    'uses' => 'CarCategoryController@store',
    'as' => 'store',
    'middleware' => [
      'auth:admin'
    ]
  ]);

  Route::group([
    'prefix' => '{id}'
  ], function () {
    Route::get('/fetch', [
      'uses' => 'CarCategoryController@show',
      'as' => 'show'
    ]);
    Route::post('update', [
      'uses' => 'CarCategoryController@update',
      'as' => 'update',
      'middleware' => [
        'auth:admin'
      ]
    ]);
  });
});

Route::group([
  'namespace' => 'App\Modules\Car\Controllers\Dashboard',
  'prefix' => 'admin/cars/city-pricing',
  'as' => 'admin.cars.city-pricing.',
  'middleware' => [
    'auth:admin'
  ]
], function () {
  Route::get('/', [
    'uses' => 'CarCategoryCityPricingController@index',
    'as' => 'index'
  ]);

  Route::post('/', [
    'uses' => 'CarCategoryCityPricingController@store',
    'as' => 'store'
  ]);

  Route::group([
    'prefix' => '{id}'
  ], function () {
    Route::get('/fetch', [
      'uses' => 'CarCategoryCityPricingController@show',
      'as' => 'show'
    ]);
    Route::post('update', [
      'uses' => 'CarCategoryCityPricingController@update',
      'as' => 'update'
    ]);
    Route::delete('update', [
      'uses' => 'CarCategoryCityPricingController@destroy',
      'as' => 'destroy'
    ]);
  });
});

Route::group([
  'namespace' => 'App\Modules\Car\Controllers\Mobile',
  'prefix' => 'app/driver/cars',
  'as' => 'app.driver.cars.',
  'middleware' => ['auth:driver']
], function () {

  Route::post('choose-car', [
    'uses' => 'DriverController@chooseCar',
    'as' => 'choose-car'
  ]);

  Route::post('deselect-car', [
    'uses' => 'DriverController@deselectCar',
    'as' => 'deselect-car'
  ]);

  Route::post('sessions/change-status', [
    'uses' => 'DriverController@changeSessionStatus',
    'as' => 'change-status'
  ]);
});
