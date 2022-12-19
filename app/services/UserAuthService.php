<?php

/**
 * This service abstracts some interactions that occurs between Confide and the Database.
 */
class UserAuthService
{
	/** @var \Illuminate\Foundation\Application */
	protected $app;

	public function __construct(\Illuminate\Foundation\Application $app)
	{
		$this->app = $app;
	}

	//region Properties

	/** @var \Illuminate\Config\Repository */
	protected $_config;

	public function config()
	{
		return isset($this->_config) ? $this->_config : ($this->_config = $this->app['config']);
	}

	/** @var \Illuminate\Cache\Repository */
	protected $_cache;

	public function cache()
	{
		return isset($this->_cache) ? $this->_cache : ($this->_cache = $this->app['cache']);
	}

	/** @var \Illuminate\Auth\Guard */
	protected $_auth;

	public function auth()
	{
		return isset($this->_auth) ? $this->_auth : ($this->_auth = $this->app['auth']);
	}

	/** @var \Illuminate\Auth\Reminders\DatabaseReminderRepository */
	protected $_reminders;

	public function reminders()
	{
		return isset($this->_reminders) ? $this->_reminders : ($this->_reminders = $this->app['auth.reminder.repository']);
	}

	/** @var \Illuminate\Auth\Reminders\PasswordBroker */
	protected $_password;

	public function password()
	{
		return isset($this->_password) ? $this->_password : ($this->_password = $this->app['auth.reminder']);
	}

	//endregion

	/**
	 * Find a user by one of the fields given as $identity.
	 *
	 * @param  array $identity An array of attributes and values to search for
	 * @return \User User object
	 */
	public function getUserByCredentials(array $identity)
	{
		if (isset($identity['email'])) {
			// NOTE: MySQL RegExp specific!
			$email = '(^|,|;)[[:space:]]*' . preg_quote($identity['email']) . '[[:space:]]*(;|,|$)';
			unset($identity['email']);

			// Check whether the user exists
			$identity[null] = function ($q) use ($email) {
				/* @var $q \Illuminate\Database\Query\Builder */
				$q->where('email', 'regexp', $email)->orderBy(User::UPDATED_AT);// oldest
			};
		}

		return $this->auth()->getProvider()->retrieveByCredentials($identity);
	}

	/**
	 * @param  \User $user
	 * @throws \UnderflowException
	 */
	public function checkIfLoginLocked($user)
	{
		if ($this->cache()->get('user_login_locked') && !$user->isAdmin())
			throw new \UnderflowException('confide.user_login_locked');
	}

	//region Password Reminders

	/**
	 * If an user with the given email exists then generate a token for password
	 * change and saves it in the 'password_reminders' table with the email of the user.
	 *
	 * @param  array $input
	 * @param  \Closure $callback
	 * @return string token
	 */
	public function forgotPassword(array $input, \Closure $callback = null)
	{
		$user = $this->getUserByCredentials($input);

		if (empty($user)) {
			throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
				\Illuminate\Auth\Reminders\PasswordBroker::INVALID_USER);
		}
		$this->checkIfLoginLocked($user);

		$token = $this->requestChangePassword($user);
		if (!is_null($callback)) call_user_func($callback, $user, $token);

		return $token;
	}

	/**
	 * Generate a token for password change and saves it in
	 * the 'password_reminders' table with the email of the user.
	 *
	 * @param  \User $user An existent user.
	 * @return string Password reset token.
	 * @see \Illuminate\Auth\Reminders\PasswordBroker::remind()
	 */
	public function requestChangePassword($user)
	{
		// Once we have the reminder token, we are ready to send a message out to the user with a link to reset their password
		$token = $this->reminders()->create($user);

		if (count($emails = $user->emails) > 1)
			$user->setReminderEmail(reset($emails));

		$this->password()->sendReminder($user, $token, function ($message, $user) use ($emails) {
			/* @var $message \Illuminate\Mail\Message */
			$message->subject('Password Reset');

			if (count($emails) > 1) {
				$message->to($emails);
				/* @var $user \User */
				$user->setReminderEmail();
			}
		});

		return $token;
	}

	/**
	 * Resets a password of a user. The $input['token'] will tell which user.
	 *
	 * @param  array $input Array containing 'token' and 'password' keys.
	 * @return \User
	 * @see \Illuminate\Auth\Reminders\PasswordBroker::reset()
	 */
	public function resetPassword(array $input)
	{
		if (!isset($input['email'])) {
			$input['email'] = $this->reminders()->getConnection()->table($this->config()->get('auth.reminder.table'))
				->where('token', $input['token'])->pluck('email');

			if (empty($input['email']))
				throw new \InvalidArgumentException(\Illuminate\Auth\Reminders\PasswordBroker::INVALID_TOKEN);
		}

		/* @var $user \User */
		$user = $this->password()->getUser($input);

		if (empty($user)) {
			throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
				\Illuminate\Auth\Reminders\PasswordBroker::INVALID_USER);
		}

		if (!$this->reminders()->exists($user, $input['token']))
			throw new \InvalidArgumentException(\Illuminate\Auth\Reminders\PasswordBroker::INVALID_TOKEN);

		$user->password = $input['password'];
		if (!$user->save())
			return false;

		$this->reminders()->delete($input['token']);

		return $user;
	}

	//endregion

	/**
	 * Attempt to log a user into the application with password and identity field(s), usually email or username.
	 *
	 * @param  array $input Array containing at least 'username' or 'email' and 'password'. Optionally the 'remember' boolean.
	 * @param  bool $mustBeConfirmed If true, the user must have confirmed his email account in order to log-in.
	 * @return \User
	 */
	public function login(array $input, $mustBeConfirmed = null)
	{
		if (is_null($mustBeConfirmed))
			$mustBeConfirmed = $this->config()->get('auth.signup_confirm', true);

		$username = $input['username'];
		$remember = isset($input['remember']) ? filter_var($input['remember'], FILTER_VALIDATE_BOOLEAN) : false;

		if (!$this->loginThrottling($username))
			throw new \RuntimeException('confide.too_many_attempts');

		$user = $this->getUserByCredentials(compact('username'));
		if (empty($user) && filter_var($username, FILTER_VALIDATE_EMAIL) !== false)
			$user = $this->getUserByCredentials(array('email' => $username));

		if (empty($user))
			throw new \Illuminate\Database\Eloquent\ModelNotFoundException('confide.wrong_credentials');

		$this->checkIfLoginLocked($user);

		if (empty($user->confirmed) && $mustBeConfirmed)
			throw new \LogicException('confide.user_not_confirmed');

		$correctPassword = $this->auth()->getProvider()->validateCredentials($user, $input);
		if (!$correctPassword)
			throw new \Symfony\Component\Security\Core\Exception\BadCredentialsException('confide.wrong_credentials');

		$this->auth()->login($user, $remember);
		return $user;
	}

	//region Login Throttle Service

	/**
	 * Returns false if the throttleCount is greater then the 'throttle_limit' config.
	 * Also sleeps a little in order to avoid dictionary attacks.
	 *
	 * @param  string $identity
	 * @return bool False if the identity has reached the 'throttle_limit'.
	 */
	protected function loginThrottling($identity)
	{
		$count = $this->countThrottle($identity);

		if ($count >= $this->config()->get('auth.throttle_limit', 9))
			return false;

		// Throttling delay!
		// See: http://www.codinghorror.com/blog/2009/01/dictionary-attacks-101.html
		if ($count > 2)
			usleep(($count - 1) * 400000);

		return true;
	}

	/**
	 * Increments the count for the given string by one stores
	 * it into cache and returns the current value for that identity.
	 *
	 * @param  string $identityString
	 * @param  int $increments Amount that is going to be added to the throttling attempt for the given identity.
	 * @return int How many times that same string was used.
	 */
	protected function countThrottle($identityString, $increments = 1)
	{
		$identityString = 'login_throttling:' . md5($identityString);

		$count = $this->cache()->get($identityString, 0);
		$count += $increments;

		$ttl = $this->config()->get('auth.throttle_time_period', 2);
		$this->cache()->put($identityString, $count, $ttl);

		return $count;
	}

	//endregion

	/**
	 * Signup a new account with the given parameters.
	 *
	 * @param  array $input Array containing 'username' and 'password'.
	 * @return \User|bool User object that may or may not be saved successfully.
	 */
	public function signup(array $input)
	{
		$user = $this->getUserByCredentials(array_only($input, 'username') + ['password' => '']); //new User;
		if (empty($user))
			return false;

		//$user->username = array_get($input, 'username');
		//$user->email = array_get($input, 'email');
		//$user->password = array_get($input, 'password');

		// Generate a random confirmation code
		$user->confirmation_code = md5(uniqid(mt_rand(), true));

		// Save if valid. Password field will be hashed before save
		//$user->save();

		unset($input['username'], $input['password_confirmation']);
		$user->update($input);

		return $user;
	}

	/**
	 * Sets the 'confirmed' field of the user with the matching code to true.
	 *
	 * @param  string $code
	 * @return \User|bool Success
	 */
	public function confirmByCode($code)
	{
		$user = $this->getUserByCredentials(['confirmation_code' => $code]);

		return empty($user) ? false : $this->confirmUser($user);
	}

	/**
	 * Updated the given user in the database. Set the 'confirmed' attribute to true.
	 *
	 * @param  \User $user User object
	 * @return \User|bool Success
	 */
	protected function confirmUser($user)
	{
		// Is confirmed?
		if (!empty($user->confirmed))
			return $user;

		// Is confirmation expired?
		$expire = $this->config()->get('auth.reminder.expire', 1440);
		if ($user->updated_at < \Carbon\Carbon::parse('-' . $expire . ' minute')) {

			$user->password = '';
			$user->confirmation_code = null;
			$user->save();

			return false;
		}

		$user->confirmed = true;
		return (!$user->save()) ? false : $user;
	}

}
