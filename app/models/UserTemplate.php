<?php

/**
 * User, CustomPage and ThemeLayout relations.
 *
 * @property int $id
 * @property int $user_id
 * @property int $page_id
 * @property int $theme_id
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read CustomPage $template
 * @property-read ThemeLayout $theme
 *
 * @method static \Illuminate\Database\Eloquent\Builder|UserTemplate whereUserId($userId)
 * @method static \Illuminate\Database\Eloquent\Builder|UserTemplate whereUserIdAndPageId($userId, $pageId)
 */
class UserTemplate extends \Eloquent
{
	const TABLE = 'user_custom_pages';

	/**
	 * The database table used by the model.
	 */
	protected $table = self::TABLE;

	/**
	 * The attributes excluded from the model's JSON form.
	 */
	protected $hidden = array('user', 'template', 'theme');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id'/*, 'user_id', 'page_id'*/);

	//region Relationships

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|User
	 */
	public function user()
	{
		return $this->belongsTo('User', 'user_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|CustomPage
	 */
	public function template()
	{
		return $this->belongsTo('CustomPage', 'page_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|ThemeLayout
	 */
	public function theme()
	{
		return $this->belongsTo('ThemeLayout', 'theme_id');
	}

	//endregion

}
