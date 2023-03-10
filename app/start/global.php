<?php

/*
|--------------------------------------------------------------------------
| Register The Laravel Class Loader
|--------------------------------------------------------------------------
|
| In addition to using Composer, you may use the Laravel class loader to
| load your controllers and models. This is useful for keeping all of
| your classes in the "global" namespace without Composer updating.
|
*/

ClassLoader::addDirectories(array(

	app_path().'/commands',
	app_path().'/controllers',
	app_path().'/models',
	app_path().'/database/seeds',
	app_path().'/services',

));

/*
|--------------------------------------------------------------------------
| Application Error Logger
|--------------------------------------------------------------------------
|
| Here we will configure the error logger setup for the application which
| is built on top of the wonderful Monolog library. By default we will
| build a basic log file setup which creates a single file for logs.
|
*/

Log::useDailyFiles(storage_path().'/logs/laravel.log');

/*
|--------------------------------------------------------------------------
| Application Error Handler
|--------------------------------------------------------------------------
|
| Here you may handle any errors that occur in your application, including
| logging them or displaying custom views for specific errors. You may
| even register several error handlers to handle different types of
| exceptions. If nothing is returned, the default error view is
| shown, which includes a detailed stack trace during debug.
|
*/

App::error(function(Exception $exception, $code)
{
	Log::error((!$exception->getMessage() ? Request::getRequestUri() : '') . $exception);

	if (php_sapi_name() != 'cli' && !Request::ajax() && !Request::wantsJson()) {
		$data = array('message' => $exception->getMessage());
		if (Config::get('app.debug')) {
			$data['exception'] = $exception->__toString();
		}

		return Response::view('errors.fatal', $data, $code,
			($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) ? $exception->getHeaders() : array());
	}

	return null;
});

App::missing(function(Exception $exception)
{
	if (php_sapi_name() != 'cli' && !Request::ajax() && !Request::wantsJson())
		return Response::view('errors.missing', array('message' => $exception->getMessage()), 404);

	return null;
});

/*
|--------------------------------------------------------------------------
| Maintenance Mode Handler
|--------------------------------------------------------------------------
|
| The "down" Artisan command gives you the ability to put an application
| into maintenance mode. Here, you will define what is displayed back
| to the user if maintenance mode is in effect for the application.
|
*/

App::down(function()
{
	return Response::make('Be right back!', 503);
});

/*
|--------------------------------------------------------------------------
| IoC Container
|--------------------------------------------------------------------------
|
*/
Cache::extend('extFile', function ($app) {
	$store = new ExtFileCacheStore($app['files'], $app['config']['cache.path'], $app['config']['cache.lottery']);
	return new \Illuminate\Cache\Repository($store);
});

Auth::extend('cachedEloquent', function ($app) {
	// Return implementation of Illuminate\Auth\UserProviderInterface
	return new CachedUserProvider($app['hash'], $app['config']['auth.model'],
		$app['cache.store'], $app['config']['session.lifetime']);
});

Validator::resolver(function ($translator, $data, $rules, $messages) {
	return new CustomValidator($translator, $data, $rules, $messages);
});
CustomValidator::extendLaravalid(Form::converter()->rule(), Form::converter()->message());

/*
|--------------------------------------------------------------------------
| Require The Filters File
|--------------------------------------------------------------------------
|
| Next we will load the filters file for the application. This gives us
| a nice separate location to store our route and application filter
| definitions instead of putting them all in the main routes file.
|
*/
/** @noinspection PhpIncludeInspection */
require app_path().'/filters.php';

/** @noinspection PhpIncludeInspection */
require app_path().'/helpers.php';

try {
	DB::disableQueryLog();  // speed up

	Queue::looping(function() { // delay for emails sending daemon
		@sleep(1);
		return true;
	});
} catch (\Exception $e) {
	Log::warning($e->getMessage());
}
