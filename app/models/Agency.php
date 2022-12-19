<?php

/**
 * @property int $id
 *
 * @property string $name
 * @property string $main_phone
 * @property string $toll_free_number
 * @property string $phone
 *
 * @property string $address
 * @property string $city
 * @property string $state
 * @property string $zip
 *
 * @property string $fax
 * @property string $website
 *
 * @property string $seller_of_travel
 * @property string $print_marketing_version
 * @property string $logo
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Relations\Pivot|object $pivot   Virtual property!
 * @property-read \Illuminate\Database\Eloquent\Collection|User[] $users
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Agency whereId($id)
 * @method static \Illuminate\Database\Eloquent\Builder|Agency whereName($name)
 */
class Agency extends \Eloquent
{
	use EmailAttributesTrait;

	const TABLE = 'agencies';

	/**
	 * The database table used by the model.
	 */
	protected $table = self::TABLE;

	/**
	 * The attributes excluded from the model's JSON form.
	 */
	protected $hidden = array('users');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id');

	//region Relationships

	/**
	 * Many-to-Many relations with User.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Eloquent\Builder|User
	 */
	public function users()
	{
		return $this->belongsToMany('User', 'agency_contacts', 'agency_id', 'user_id')
			->withPivot('is_lead');
	}

	//endregion

	//region Mutator Properties

	//endregion

	//region Scopes

	//endregion

}
