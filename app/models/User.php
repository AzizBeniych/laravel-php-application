<?php

/**
 * @property int $id
 *
 * @property string $username
 * @property string $password
 * @property string $confirmation_code
 * @property string $remember_token
 * @property bool $confirmed
 *
 * @property string $name
 * @property string $local_phone
 * @property string $toll_free_number
 *
 * @property string $address
 * @property string $city
 * @property string $state
 * @property string $zip
 *
 * @property string $fax
 * @property string $website
 *
 * @property string $cst_number
 * @property string $print_marketing_version
 * @property string $photo
 * @property string $bio
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 *
 * @property array|string[] $role_names Virtual field!
 * @property-read Agency $agency        The primary company.
 * @property-read string $welcome_name
 * @property-read \Illuminate\Database\Eloquent\Relations\Pivot|object $pivot   Virtual property!
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|Agency[] $agencies
 * @property-read \Illuminate\Database\Eloquent\Collection|Role[] $roles
 * @property-read \Illuminate\Database\Eloquent\Collection|UserElementValue[] $layoutValues
 * @property-read \Illuminate\Database\Eloquent\Collection|UserPhoto[] $photos
 *
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($id)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($username)
 */
class User extends \Eloquent implements \Illuminate\Auth\UserInterface, \Illuminate\Auth\Reminders\RemindableInterface
{
	use \Illuminate\Auth\UserTrait, IntSoftDeletingTrait, EmailAttributesTrait;

	const TABLE = 'users';
	const DELETED_AT = 'deleted_at';

	/**
	 * The database table used by the model.
	 */
	protected $table = self::TABLE;

	/**
	 * Soft Deleting.
	 */
	protected $dates = array(self::DELETED_AT);

	/**
	 * The attributes excluded from the model's JSON form.
	 */
	protected $hidden = array(self::DELETED_AT, 'password', 'remember_token', 'agencies', 'roles');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id', self::DELETED_AT, 'remember_token', 'confirmation_code', 'confirmed');

	//region Relationships

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Eloquent\Builder|Agency
	 */
	public function agencies()
	{
		// TODO: $relation->orderBy('is_lead', 'desc')->orderBy(Agency::TABLE . '.' . Agency::UPDATED_AT, 'desc')
		return $this->belongsToMany('Agency', 'agency_contacts', 'user_id', 'agency_id')
			->withPivot('is_lead');
	}

	/**
	 * One-to-Many relations with Role.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Eloquent\Builder|Role
	 */
	public function roles()
	{
		return $this->hasMany('Role', 'user_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder|UserElementValue
	 */
	public function layoutValues()
	{
		return $this->hasMany('UserElementValue', 'user_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder|UserPhoto
	 */
	public function photos()
	{
		return $this->hasMany('UserPhoto', 'user_id');
	}

	//endregion

	//region Mutator Properties

	/**
	 * Automatically Hash passwords when they are saved/updated.
	 *
	 * @param string
	 */
	public function setPasswordAttribute($value)
	{
		$this->attributes['password'] = (!empty($value) && \Hash::needsRehash($value)) ? \Hash::make($value) : $value;
	}

	protected $_currentAgency;

	/**
	 * @return Agency|NULL
	 */
	public function getAgencyAttribute()
	{
		return isset($this->_currentAgency) ? $this->_currentAgency
			: ($this->_currentAgency = !($list = $this->agencies) ? null : $list->first());
	}

	/**
	 * @param  int|Agency
	 */
	public function setAgencyAttribute($value)
	{
		if ($list = $this->agencies) {
			$id = isset($value->id) ? $value->id : intval($value);
			$this->_currentAgency = $list->find($id) ?: $list->get($id);
		} else {
			$this->_currentAgency = null;
		}
	}

	/**
	 * @return string
	 */
	public function getWelcomeNameAttribute()
	{
		return $this->name . (is_null($c = $this->agency) ? '' : ' - ' . $c->name);
	}

	/**
	 * @return array|string[]
	 */
	public function getRoleNamesAttribute()
	{
		return !($list = $this->roles) ? array() : $list->lists('name');
	}

	/**
	 * @param  array|string
	 */
	public function setRoleNamesAttribute($value)
	{
		if (empty($value))
			$value = array();
		elseif (!is_array($value))
			$value = preg_split('/\s*[,;]\s*/u', trim($value));

		foreach ($this->roles as $role) {
			if (!in_array($role->name, $value))
				$detached[] = $role->getKey();
			else
				$attached[] = $role->name;
		}

		if (isset($detached))
			$this->roles()->detach($detached);

		if (isset($attached))
			$value = array_diff($value, $attached);

		foreach ($value as $name) {
			$this->roles()->create(compact('name'));
		}
	}

	//endregion

	//region Roles Ability

	/**
	 * Checks if the user has a Role by its name.
	 *
	 * @param string|array $names Role name(s).
	 * @return bool
	 */
	public function hasRole($names = null)
	{
		if (!is_array($names))
			$names = func_get_args();

		if (in_array(Role::LEADER, $names) && $this->isLeader())
			return true;

		foreach ($this->roles as $role) {
			if (in_array($role->name, $names))
				return true;
		}
		return false;
	}

	//endregion

	//region Reminder Interface

	protected $_reminderEmail;

	/**
	 * Get the e-mail address where password reminders are sent.
	 *
	 * @return string
	 */
	public function getReminderEmail()
	{
		return isset($this->_reminderEmail) ? $this->_reminderEmail : $this->email;
	}

	public function setReminderEmail($value = null)
	{
		$this->_reminderEmail = $value;
	}

	//endregion

	protected $_isAdmin;

	/**
	 * @return bool If has role ADMIN or MANAGER
	 */
	public function isAdmin()
	{
		return isset($this->_isAdmin) ? $this->_isAdmin
			: ($this->_isAdmin = $this->hasRole(Role::ADMIN, Role::MANAGER));
	}

	/**
	 * @return bool
	 */
	public function isLeader()
	{
		return (!is_null($c = $this->agency) && isset($c->pivot) && $c->pivot->is_lead);
	}

	//region Scopes

	//endregion

	//region Cache Methods

	/**
	 * @param  int $userId
	 * @return User
	 */
	public static function pickCache($userId)
	{
		return \Cache::get(CachedUserProvider::cacheKey($userId));
	}

	/**
	 * @param  int $userId
	 */
	public static function evictCache($userId)
	{
		$cacheKey = CachedUserProvider::cacheKey($userId);
		// DEBUG
		if (Config::get('app.debug')) {
			$dbt = debug_backtrace(false, 2);
			Log::debug('Try to evict cached user: ' . $cacheKey . '  from: ' . (isset($dbt[1]['function']) ? $dbt[1]['function'] : '_unknown'));
		}

		\Cache::forget($cacheKey);
	}

	//endregion

	//region Photo Properties

	//endregion

	//region Approval Requests

	//endregion

}
