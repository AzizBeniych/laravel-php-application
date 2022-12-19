<?php

/**
 * Implements actions regarding administration.
 */
class AdminController extends BaseController
{

	public function index()
	{
		return View::make('admin.index');
	}

}
