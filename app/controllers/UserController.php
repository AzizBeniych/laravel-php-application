<?php

/**
 * Implements user-account actions
 */
class UserController extends BaseController
{
	/** @var \UserAuthService */
	protected $repo;

	/**
	 * UserController constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->repo = App::make('UserAuthService');
	}

	private static $loginFormValidation = [
		'username' => 'required|max:50|username',
		'password' => 'required|max:50',
		'remember' => 'boolean',
	];

	/**
	 * Displays the login form.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function login()
	{
		$user = Auth::user();
		if (!empty($user)) {
			User::evictCache($user->getKey());

			return Redirect::route('dashboard');
		}

		Form::setValidation(static::$loginFormValidation);
		return View::make('login');
	}

	/**
	 * Attempt to do login.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function doLogin()
	{
		$inputs = Input::only(array_keys(static::$loginFormValidation));
		$validator = Validator::make($inputs, static::$loginFormValidation);

		if ($validator->fails()) {
			return Redirect::action('UserController@login')
				->withInput(array_except($inputs, 'password'))
				->withErrors($validator);
		}

		// try to login
		try {
			$user = $this->repo->login($inputs);
			User::evictCache($userId = $user->id);

			Session::put('last_login_date', ActionLog::fetchLastLoginDate($userId));
			// Log action
			ActionLog::tryCreate(ActionLog::ACTION_LOGIN, ActionLog::OBJECT_USER, $userId, $userId);

		} catch (\Exception $e) {
			if (!preg_match('/^[\w\.]+$/', $msg = $e->getMessage())) Log::error($e);
			else $msg = Lang::get($msg);

			return Redirect::action('UserController@login')
				->withInput(array_except($inputs, 'password'))
				->with('error', $msg);
		}

		return Redirect::intended(route('dashboard'));
	}

	/**
	 * Log the user out of the application.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function logout()
	{
		$userId = Auth::id();
		Auth::logout();
		Session::flush();

		if (!empty($userId)) {
			User::evictCache($userId);
			// Log action
			ActionLog::tryCreate(ActionLog::ACTION_LOGOUT, ActionLog::OBJECT_USER, $userId, $userId);
		}

		return Redirect::route('home');
	}

}
