<?php

/**
 * @property int $id
 * @property int $magazine_id
 *
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property string $preview_image
 *
 * @property bool $pre_designed
 * @property int $order
 * @property array|string $specific_roles   Comma-separated role names
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 *
 * @property-read Magazine $magazine
 * @property-read \Illuminate\Database\Eloquent\Collection|ThemeLayout[] $layouts
 * @property-read \Illuminate\Database\Eloquent\Collection|UserTemplate[] $userPages
 *
 * @method static \Illuminate\Database\Eloquent\Builder|CustomPage whereId($id)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomPage whereName($name)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomPage preDesigned($not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|CustomPage forRoles($roles = array(), $excludeLayouts = false)
 */
class CustomPage extends \Eloquent
{
	use IntSoftDeletingTrait;

	const TABLE = 'custom_pages';
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
	protected $hidden = array(self::DELETED_AT, 'magazine', 'layouts', 'userPages');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id', self::DELETED_AT);

	//region Relationships

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|Magazine
	 */
	public function magazine()
	{
		return $this->belongsTo('Magazine', 'magazine_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder|ThemeLayout
	 */
	public function layouts()
	{
		return $this->hasMany('ThemeLayout', 'page_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder|UserTemplate
	 */
	public function userPages()
	{
		return $this->hasMany('UserTemplate', 'page_id');
	}

	//endregion

	//region Mutator Properties

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

	public function scopePreDesigned($query, $not = false)
	{
		/* @var $query \Illuminate\Database\Eloquent\Builder */
		return $query->where('pre_designed', !$not ? '<>' : '=', false);
	}

	public function scopeForRoles($query, $roles = array(), $excludeLayouts = false)
	{
		/* @var $query \Illuminate\Database\Eloquent\Builder */
		if (!$excludeLayouts) {
			$query = $query->whereHas('layouts', function ($q) use ($roles) {
				/* @var $q ThemeLayout */
				$q->forRoles($roles);
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

	//region PDF template images

	const BASE_PATH = 'files/templates';

	/**
	 * @param  CustomPage $theme
	 * @return array|string[]
	 */
	public static function listTemplateImages($theme = null)
	{
		$list = array();
		if (!empty($theme)) {
			$filter = '/[^_.-]*' . preg_quote(preg_replace('/\d+$/', '', $theme->name), '/') . '/i';

			if (!empty($theme->preview_image))
				$list[$theme->preview_image] = str_replace(static::BASE_PATH . '/', '', $theme->preview_image);
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

	/**
	 * @param  string|int $year
	 * @param  string|int $month
	 * @return string
	 */
	public static function fetchIssueImage($year, $month)
	{
		$years = array($year);
		if ($month < 2)
			array_unshift($years, $year - 1);

		// Search pattern
		$dt = strtotime("$year-$month-01");
		$filter = ($m1 = date('M', $dt)) . '[_-]?' . ($m2 = date('M', strtotime('+1 month', $dt)))
			. '|' . $m1[0] . $m2[0] . '(' . substr($year, 0, 2) . ')?' . substr($year, -2);
		$filter = '/^(o?fc\d*_)+(' . $filter . ').*\.(jpe?g|png|gif)$/i';

		$path = public_path(static::BASE_PATH);
		foreach ($years as $yy) {
			foreach (scandir("$path/$yy") as $fn) {
				if (preg_match($filter, $fn))
					return static::BASE_PATH . "/$yy/$fn";
			}
		}

		return null;
	}

	//endregion

}
