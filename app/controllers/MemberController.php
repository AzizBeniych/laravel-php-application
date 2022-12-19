<?php

class MemberController extends BaseController
{

	public function index()
	{
		// Current logged-in user roles
		$user = Auth::user();

		$magazines = Magazine::forRoles($user->role_names)->forAgency($user->agency)->orderBy('order')->get();

		return View::make('user.index')
			->with('user', $user)
			->with('magazines', $magazines);
	}

}
