<?php

/**
 * @property int $id
 * @property int $user_id
 * @property int $element_id
 * @property string $value
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 * @property-read LayoutElement $element
 *
 * @method static \Illuminate\Database\Eloquent\Builder|UserElementValue whereUserId($userId)
 * @method static \Illuminate\Database\Eloquent\Builder|UserElementValue whereElementId($fieldId)
 * @method static \Illuminate\Database\Eloquent\Builder|UserElementValue whereUserIdAndElementId($userId, $fieldId)
 */
class UserElementValue extends \Eloquent
{
	const TABLE = 'user_layout_elements';

	/**
	 * The database table used by the model.
	 */
	protected $table = self::TABLE;

	/**
	 * The attributes excluded from the model's JSON form.
	 */
	protected $hidden = array('user', 'element');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id'/*, 'user_id', 'element_id'*/);

	//region Relationships

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|User
	 */
	public function user()
	{
		return $this->belongsTo('User', 'user_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|LayoutElement
	 */
	public function element()
	{
		return $this->belongsTo('LayoutElement', 'element_id');
	}

	//endregion

}
