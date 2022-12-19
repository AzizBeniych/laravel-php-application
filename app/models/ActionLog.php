<?php

/**
 * Used to track signup/login history.
 *
 * @property int $id
 *
 * @property string $action
 * @property string $object_type
 * @property int $object_id
 * @property string $description
 *
 * @property int $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ActionLog whereCreatedBy($userId)
 * @method static \Illuminate\Database\Eloquent\Builder|ActionLog whereObjectTypeAndObjectId($type, $id)
 * @method static \Illuminate\Database\Eloquent\Builder|ActionLog whereActionAndObjectTypeAndObjectId($action, $type, $id)
 */
class ActionLog extends \Eloquent
{
	//region Constants

	const ACTION_LOGIN = 'Login';
	const ACTION_LOGOUT = 'Logout';
	const ACTION_FORGOT_PASSWORD = 'ForgotPassword';

	const ACTION_GENERATE = 'Generate';
	const ACTION_ACTIVATE = 'Activate';
	const ACTION_REACTIVATE = 'Reactivate';
	const ACTION_IMPORT = 'Import';
	const ACTION_EXPORT = 'Export';

	const ACTION_ADD = 'Add';
	const ACTION_UPDATE = 'Update';
	const ACTION_DELETE = 'Delete';
	const ACTION_REMOVE = 'Remove';
	const ACTION_RESTORE = 'Restore';

	const ACTION_VISIT = 'Visit';
	const ACTION_VIEW = 'View';
	const ACTION_READ = 'Read';
	const ACTION_LIST = 'List';

	const ACTION_SUBSCRIBE = 'Subscribe';
	const ACTION_UNSUBSCRIBE = 'Unsubscribe';
	const ACTION_LOCK = 'Lock';
	const ACTION_UNLOCK = 'Unlock';
	const ACTION_APPROVE = 'Approve';
	const ACTION_UNAPPROVE = 'Unapprove';


	const OBJECT_USER = 'User';
	const OBJECT_USER_PASSWORD = 'UserPassword';

	const OBJECT_SUBSCRIBER = 'Subscriber';
	const OBJECT_USER_PHOTO = 'UserPhoto';

	const OBJECT_CUSTOM_PAGE = 'CustomPage';
	const OBJECT_THEME_LAYOUT = 'ThemeLayout';
	const OBJECT_THEME_FIELD = 'ThemeField';
	const OBJECT_LAYOUT_ELEMENT = 'LayoutElement';

	const OBJECT_USER_TEMPLATE = 'UserTemplate';
	const OBJECT_USER_THEME = 'UserTheme';
	const OBJECT_USER_FIELD = 'UserThemeField';

	const OBJECT_APPROVAL_REQUEST = 'ApprovalRequest';


	protected static $_ACTIONS;
	protected static $_OBJECT_TYPES;

	/**
	 * @param  string $prefix
	 * @return array
	 */
	protected static function getConstants($prefix)
	{
		$arr = array();
		foreach ((new ReflectionClass(__CLASS__))->getConstants() as $k => $v) {
			if (preg_match($prefix, $k))
				array_push($arr, $v);
		}
		return $arr;
	}

	/**
	 * @param  int $index
	 * @return array|string
	 */
	public static function actions($index = null)
	{
		if (!isset(static::$_ACTIONS)) {
			static::$_ACTIONS = static::getConstants('/^ACTION_/');
		}
		return is_null($index) ? static::$_ACTIONS : static::$_ACTIONS[$index];
	}

	/**
	 * @param  int $index
	 * @return array|string
	 */
	public static function objectTypes($index = null)
	{
		if (!isset(static::$_OBJECT_TYPES)) {
			static::$_OBJECT_TYPES = static::getConstants('/^OBJECT_/');
		}
		return is_null($index) ? static::$_OBJECT_TYPES : static::$_OBJECT_TYPES[$index];
	}

	//endregion

	const TABLE = 'action_logs';

	/**
	 * The database table used by the model.
	 */
	protected $table = self::TABLE;

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id');

	/**
	 * @param  string $action
	 * @param  string $object_type
	 * @param  int $object_id
	 * @param  int $created_by
	 * @param  string|array|object $description
	 * @return ActionLog|bool
	 */
	public static function tryCreate($action, $object_type, $object_id, $created_by, $description = null)
	{
		try {
			if (isset($description) && (is_array($description) || is_object($description)))
				$description = json_encode($description, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);

			$model = new static(compact('action', 'object_type', 'object_id', 'created_by', 'description'));

			if ($model->save())
				return $model;

		} catch (\Exception $e) {
			Log::warning($e);
		}

		return false;
	}

	/**
	 * @param  int|array $userIds
	 * @return \DateTime|array
	 */
	public static function fetchLastLoginDate($userIds)
	{
		if (empty($userIds))
			return null;

		if (!is_array($userIds)) {
			$userIds = array($userIds);
			$singleResult = true;
		}

		$results = static::whereActionAndObjectTypeAndObjectId(static::ACTION_LOGIN, static::OBJECT_USER, DB::raw('created_by'))
			->whereIn('object_id', $userIds)
			->groupBy('object_id')
			->select(array('object_id', DB::raw('MAX(' . static::CREATED_AT . ') AS ' . static::CREATED_AT)))
			->lists(static::CREATED_AT, 'object_id');

		$results = array_replace(array_fill_keys($userIds, null), $results);
		return empty($singleResult) ? $results : $results[$userIds[0]];
	}

}
