<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
  dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
  dirname(__DIR__)
);

$app->withFacades(true, [
  Illuminate\Support\Facades\Notification::class => 'Notification',
]);

$app->withEloquent();

$app->configure('database');

$app->configure('jwt');

$app->configure('services');

$app->configure('mail');

$app->configure('filesystems');

$app->configure('cashier');

$app->configure('queue');

$app->configure('database');

$app->configure('laravel-fcm');

$app->configure('stripe-webhooks');

$app->configure('logging');

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
  Illuminate\Contracts\Debug\ExceptionHandler::class,
  App\Exceptions\Handler::class
);

$app->singleton(
  Illuminate\Contracts\Console\Kernel::class,
  App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
//    App\Http\Middleware\AESEncryption::class,
                   App\Http\Middleware\Localization::class,
//    Tymon\JWTAuth\Http\Middleware\RefreshToken::class,
                   App\Http\Middleware\Logger::class,
                   App\Http\Middleware\CorsMiddleware::class
                 ]);

$app->routeMiddleware([
                        'auth' => App\Http\Middleware\Authenticate::class,
                      ]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

/* beautymail setup */
$app->instance('path.config', env("STORAGE_DIR", app()->basePath()) . DIRECTORY_SEPARATOR . 'config');
$app->instance('path.public', env("STORAGE_DIR", app()->basePath()) . DIRECTORY_SEPARATOR . 'public');
$app->configure('beautymail');
class_alias(\Illuminate\Support\Facades\Request::class, "\Request");
class_alias(\Illuminate\Support\Facades\Config::class, "\Config");


$app->register(App\Providers\AppServiceProvider::class);
$app->register(Ahilan\Apple\AppleHelperServiceProvider::class);
$app->register(App\Providers\AuthServiceProvider::class);
$app->register(App\Providers\EventServiceProvider::class);
$app->register(Illuminate\Redis\RedisServiceProvider::class);
$app->register(Tymon\JWTAuth\Providers\LumenServiceProvider::class);
$app->register(App\Modules\HMVCServiceProvider::class);
$app->register(Laravel\Socialite\SocialiteServiceProvider::class);
$app->register(Kreait\Laravel\Firebase\ServiceProvider::class);
$app->register(Intervention\Image\ImageServiceProviderLumen::class);
$app->register(NotificationChannels\Twilio\TwilioProvider::class);
$app->register(Illuminate\Notifications\NotificationServiceProvider::class);
$app->register(Illuminate\Mail\MailServiceProvider::class);
$app->register(Laravolt\Avatar\LumenServiceProvider::class);
$app->register(Kawankoding\Fcm\FcmServiceProvider::class);
$app->register(Mastani\GoogleStaticMap\GoogleStaticMapServiceProvider::class);
//$app->register(GeneaLabs\LaravelSignInWithApple\Providers\ServiceProvider::class);
$app->register(Ichtrojan\Otp\OtpServiceProvider::class);

$app->alias('mail.manager', Illuminate\Mail\MailManager::class);
$app->alias('mail.manager', Illuminate\Contracts\Mail\Factory::class);
$app->alias('Avatar', Laravolt\Avatar\Facade::class);
$app->alias('FCM', Kawankoding\Fcm\FcmFacade::class);
$app->alias('OTP', Ichtrojan\Otp\Otp::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
                      'namespace' => 'App\Http\Controllers',
                    ], function ($router) {
  require __DIR__.'/../routes/web.php';
});


return $app;
