<?php

/**
 * @property int $id
 *
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property string $preview_image
 *
 * @property int $order
 * @property array|string $specific_agencies    Comma-separated agency id(s)
 * @property array|string $specific_roles       Comma-separated role names
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|CustomPage[] $pages
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Magazine whereId($id)
 * @method static \Illuminate\Database\Eloquent\Builder|Magazine whereName($name)
 * @method static \Eloquent|Magazine forAgency($agency = null)
 * @method static \Illuminate\Database\Eloquent\Builder|Magazine forRoles($roles = array(), $excludePages = false)
 */
class Magazine extends \Eloquent
{
	use IntSoftDeletingTrait;

	const TABLE = 'magazines';
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
	protected $hidden = array(self::DELETED_AT, 'pages');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id', self::DELETED_AT);

	//region Relationships

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder|CustomPage
	 */
	public function pages()
	{
		return $this->hasMany('CustomPage', 'magazine_id');
	}

	//endregion

	//region Mutator Properties

	/**
	 * @param  string $value
	 * @return array
	 */
	public function getSpecificAgenciesAttribute($value = null)
	{
		return empty($value) ? array() : explode(',', $value);
	}

	/**
	 * @param  array|string $value
	 */
	public function setSpecificAgenciesAttribute($value)
	{
		if (!is_array($value))
			$value = $this->getSpecificAgenciesAttribute($value);

		$this->attributes['specific_agencies'] = empty($value) ? null : implode(',', $value);
	}

	/**
	 * @param  string $value
	 * @return array
	 */
	public function getSpecificRolesAttribute($value = null)
	{
		return empty($value) ? array() : explode(',', $value);
	}

	/**
	 * @param  array|string $value
	 */
	public function setSpecificRolesAttribute($value)
	{
		if (!is_array($value))
			$value = $this->getSpecificRolesAttribute($value);

		$this->attributes['specific_roles'] = empty($value) ? null : implode(',', $value);
	}

	//endregion

	//region Scopes

	public function scopeForAgency($query, $agency = null)
	{
		if (empty($agency))
			return $query;

		/* @var $agency Agency|int */
		$id = is_numeric($agency) ? $agency : $agency->id;

		/* @var $query \Illuminate\Database\Query\Builder */
		return $query->whereNull('specific_agencies')->orWhere('specific_agencies', '=', '')
			->orWhere('specific_agencies', 'regexp', '[[:<:]]' . $id . '[[:>:]]');
	}

	public function scopeForRoles($query, $roles = array(), $excludePages = false)
	{
		/* @var $query \Illuminate\Database\Eloquent\Builder */
		if (!$excludePages) {
			$query = $query->whereHas('pages', function ($q) use ($roles) {
				/* @var $q CustomPage */
				$q->forRoles($roles, false);
			});
		}

		return $query->where(function ($q) use ($roles) {
			/* @var $q \Illuminate\Database\Query\Builder */
			$q = $q->whereNull('specific_roles')->orWhere('specific_roles', '=', '');
			foreach ($roles as $role)
				$q = $q->orWhere('specific_roles', 'regexp', '[[:<:]]' . $role . '[[:>:]]');// NOTE: MySQL RegExp specific!
		});
	}

	//endregion

	//region Magazine images

	const BASE_PATH = 'files/magazines';

	/**
	 * @param  Magazine $magazine
	 * @return array|string[]
	 */
	public static function listMagazineImages($magazine = null)
	{
		$list = array();
		if (!empty($magazine)) {
			$filter = '/[^_.-]*' . preg_quote(preg_replace('/\d+$/', '', $magazine->name), '/') . '/i';

			if (!empty($magazine->preview_image))
				$list[$magazine->preview_image] = str_replace(static::BASE_PATH . '/', '', $magazine->preview_image);
		}

		$path = public_path(static::BASE_PATH);
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::UNIX_PATHS));
		/* @var $file \SplFileInfo */
		foreach ($iterator as $file) {
			if ($file->isFile() && in_array(strtolower($file->getExtension()), ['jpg', 'png', 'jpeg', 'gif'])
				&& (empty($filter) || preg_match($filter, $file->getFilename()))
			) {
				$fn = substr($file->getPathname(), strlen($path) + 1);
				$list[static::BASE_PATH . '/' . $fn] = $fn;
			}
		}

		ksort($list);
		return $list;
	}

	//endregion

}
