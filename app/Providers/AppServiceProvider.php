<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
      Validator::extend('enabled', function ($attribute, $value, $parameters, $validator) {
        return !!DB::table($parameters[0])->where(['status' => 'enabled', 'id' => $value])->first();
      },__('labels.enabled_message'));

      Validator::extend('owned_by_user', function ($attribute, $value, $parameters, $validator) {
        return !!DB::table($parameters[0])->where(['user_id' => $parameters[1], 'id' => $value])->first();
      },"Entity isn't associated for logged in user");
    }
}
