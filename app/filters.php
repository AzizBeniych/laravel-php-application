<?php

/*
|--------------------------------------------------------------------------
| Application & Route Filters
|--------------------------------------------------------------------------
|
| Below you will find the "before" and "after" events for the application
| which may be used to do any work before or after a request into your
| application. Here you may also register your custom route filters.
|
*/

App::before(function($request)
{
	//
});

/** @noinspection PhpUnusedParameterInspection */
App::after(function($request, $response)
{
	// Prevent browser caching
	if (Auth::check() && !($response->headers->has('Cache-Control') && $response->headers->has('Pragma'))) {
		$response->headers->set('Cache-Control', 'no-store,no-cache,must-revalidate,max-age=0');
		$response->headers->set('Pragma', 'no-cache');  //HTTP 1.0
		$response->headers->set('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	}
});

/*
|--------------------------------------------------------------------------
| Authentication Filters
|--------------------------------------------------------------------------
|
| The following filters are used to verify that the user of the current
| session is logged into this application. The "basic" filter easily
| integrates HTTP Basic authentication for quick, simple checking.
|
*/

Route::filter('auth', function()
{
	if (Auth::guest())
	{
		if (Request::ajax() || Request::wantsJson())
		{
			return Response::make('Unauthorized', 401);
		}
		else
		{
			$r = Redirect::guest('login');
			return !Session::has('_old_input') ? $r : $r->with('error', Lang::get('confide.session_expired'));
		}
	}

	return null;
});

Route::filter('auth.role', function()
{
	$user = Auth::user();
	if (!is_null($user)) {

		$value = array_slice(func_get_args(), 2);
		$roles = empty($value) ? array() : explode(',', strtoupper(implode(',', $value)));

		if (!$user->hasRole($roles)) {
			return (Request::ajax() || Request::wantsJson())
				? Response::make('Access Denied', 403) : Redirect::to('user');
		}
	}

	return null;
});

Route::filter('auth.basic', function()
{
	return Auth::basic();
});

/*
|--------------------------------------------------------------------------
| Guest Filter
|--------------------------------------------------------------------------
|
| The "guest" filter is the counterpart of the authentication filters as
| it simply checks that the current user is not logged in. A redirect
| response will be issued if they are, which you may freely change.
|
*/

Route::filter('guest', function()
{
	if (Auth::check()) return Redirect::to('/');
	return null;
});

/*
|--------------------------------------------------------------------------
| CSRF Protection Filter
|--------------------------------------------------------------------------
|
| The CSRF filter is responsible for protecting your application against
| cross-site request forgery attacks. If this special token in a user
| session does not match the one given in this request, we'll bail.
|
*/

Route::filter('csrf', function()
{
	if (Session::token() !== Input::get('_token'))
	{
		throw new Illuminate\Session\TokenMismatchException;
	}
});
