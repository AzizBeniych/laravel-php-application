<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

/** Route constraint patterns **/
Route::pattern('id', '[0-9]+');
Route::pattern('token', '[0-9A-Fa-f\-]+');

/** User Account Routes **/
Route::get('login', array('as' => 'login', 'uses' => 'UserController@login'));
Route::post('login', 'UserController@doLogin');
Route::match(array('GET', 'POST'), 'logout', array('as' => 'logout', 'uses' => 'UserController@logout'));

Route::get('register', array('as' => 'register', 'uses' => 'UserController@register'));
Route::post('register', 'UserController@doRegister');
Route::get('activate/{token}', array('as' => 'activate', 'uses' => 'UserController@confirm'));

// User password reset
Route::get('forgot-password', array('as' => 'forgot-password', 'uses' => 'UserController@forgotPassword'));
Route::post('forgot-password', 'UserController@doForgotPassword');
Route::get('reset-password/{token}', array('as' => 'reset-password', 'uses' => 'UserController@doResetPassword'));

/** Frontend Routes **/
Route::group(array('prefix' => 'site'), function () {
	Route::get('privacy', array('as' => 'privacy', function () {
		return View::make('privacy');
	}));
});

/** Administration Routes **/
Route::group(array('prefix' => 'admin', 'before' => 'auth|auth.role:' . Role::ADMIN . ',' . Role::MANAGER), function () {
	Route::get('/', array('as' => 'dashboard', 'uses' => 'AdminController@index'));

});

/** User Routes **/
Route::group(array('prefix' => 'user', 'before' => 'auth'), function () {
	Route::get('/', array('as' => 'user-home', 'uses' => 'MemberController@index'));

});

# Index Page - Last route, no matches
Route::get('/', array('as' => 'home', 'uses' => 'HomeController@index'));
