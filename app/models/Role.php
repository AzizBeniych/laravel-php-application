<?php

/**
 * @property int $id
 *
 * @property int $user_id
 * @property string $name
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Role whereName($name)
 */
class Role extends \Eloquent
{
	//region Constants

	const ADMIN = 'ADMIN';
	const MANAGER = 'MANAGER';
	const USER = 'USER';

	const LEADER = 'LEADER';

	public static $ALL = array(self::USER, self::MANAGER, self::ADMIN);
	public static $FA_ROLES = array(self::USER, self::MANAGER, self::LEADER);

	//endregion

	const TABLE = 'user_roles';

	/**
	 * The database table used by the model.
	 */
	protected $table = self::TABLE;

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id');

	//region Relationships

	/**
	 * Many-to-One relations with User.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|User
	 */
	public function user()
	{
		return $this->belongsTo('User', 'user_id');
	}

	//endregion

	//region Scopes

	//endregion

}
