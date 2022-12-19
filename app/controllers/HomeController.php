<?php

/**
 * Default Home Controller
 *
 * To route to this controller, just add the route:
 * <code>Route::get('/', 'HomeController@index');</code>
 */
class HomeController extends BaseController
{

	public function index()
	{
		//return View::make('index');
		return Redirect::route('login');
	}

}
