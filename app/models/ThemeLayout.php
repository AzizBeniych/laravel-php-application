<?php

/**
 * @property int $id
 * @property int $page_id
 *
 * @property string $name
 * @property string $display_name
 * @property string $description
 * @property string $category
 *
 * @property string $preview_image
 * @property string $pdf_file
 * @property string $generator
 *
 * @property int $order
 * @property array|string $specific_roles   Comma-separated role names
 *
 * @property bool $is_tabbed
 * @property string $instructions
 * @property array|string $override_fields  JSON array
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 *
 * @property string $generator_class        Virtual property!
 * @property string $generator_param
 *
 * @property-read CustomPage $template
 * @property-read \Illuminate\Database\Eloquent\Collection|UserTemplate[] $userPages
 * @property-read \Illuminate\Database\Eloquent\Collection|LayoutElement[] $elements
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ThemeLayout whereId($id)
 * @method static \Illuminate\Database\Eloquent\Builder|ThemeLayout wherePageId($pageId)
 * @method static \Illuminate\Database\Eloquent\Builder|ThemeLayout whereName($name)
 * @method static \Illuminate\Database\Eloquent\Builder|ThemeLayout wherePreviewImage($path)
 * @method static \Eloquent|ThemeLayout forRoles($roles = array())
 * @method static \Illuminate\Database\Eloquent\Builder|ThemeLayout byTemplateName($templateName)
 */
class ThemeLayout extends \Eloquent
{
	use IntSoftDeletingTrait;

	const TABLE = 'theme_layouts';
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
	protected $hidden = array(self::DELETED_AT, 'template', 'userPages', 'elements');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id', self::DELETED_AT);

	//region Relationships

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|CustomPage
	 */
	public function template()
	{
		return $this->belongsTo('CustomPage', 'page_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder|UserTemplate
	 */
	public function userPages()
	{
		return $this->hasMany('UserTemplate', 'theme_id');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Eloquent\Builder|LayoutElement
	 */
	public function elements()
	{
		$relation = $this->belongsToMany('LayoutElement', $table = 'theme_layout_elements', 'layout_id', 'element_id')
			->withPivot('order');
		/* @var $relation \Eloquent */
		return $relation
			->orderBy($table . '.order')->orderBy($table . '.id');
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

	/**
	 * @param  string $value
	 * @return array
	 */
	public function getOverrideFieldsAttribute($value = null)
	{
		return empty($value) ? null : (json_decode($value, true) ?: array());
	}

	/**
	 * @param  array|string $value
	 */
	public function setOverrideFieldsAttribute($value)
	{
		$this->attributes['override_fields'] = empty($value) ? null
			: ((is_array($value) || is_object($value)) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : (string)$value);
	}

	public function getGeneratorClassAttribute()
	{
		if (empty($this->generator) || ($i = strpos($this->generator, '(')) === false)
			$clsName = $this->generator;
		else
			$clsName = substr($this->generator, 0, $i);

		return ends_with($clsName, 'Generator') ? $clsName : $clsName . 'Generator';
	}

	public function setGeneratorClassAttribute($value)
	{
		$param = $this->getGeneratorParamAttribute();
		$param = empty($param) ? '' : '(' . $param . ')';

		$this->generator = (empty($value) && empty($param)) ? null : (empty($value) ? '' : $value) . $param;
	}

	public function getGeneratorParamAttribute()
	{
		if (empty($this->generator) || ($i = strpos($this->generator, '(')) === false)
			return null;

		$param = substr($this->generator, $i + 1);
		return (substr($param, -1) == ')') ? substr($param, 0, -1) : $param;
	}

	public function setGeneratorParamAttribute($value)
	{
		$clsName = $this->getGeneratorClassAttribute();
		$value = empty($value) ? '' : '(' . $value . ')';

		$this->generator = (empty($clsName) && empty($value)) ? null : (empty($clsName) ? '' : $clsName) . $value;
	}

	//endregion

	//region Scopes

	public function scopeByTemplateName($query, $templateName)
	{
		/* @var $query \Illuminate\Database\Eloquent\Builder */
		return $query->whereHas('template', function ($q) use ($templateName) {
			/* @var $q \Illuminate\Database\Eloquent\Builder */
			$q->where(CustomPage::TABLE . '.name', '=', $templateName);
		});
	}

	public function scopeForRoles($query, $roles = array())
	{
		/* @var $query \Illuminate\Database\Eloquent\Builder */
		return $query->where(function ($q) use ($roles) {
			/* @var $q \Illuminate\Database\Query\Builder */
			$q = $q->whereNull('specific_roles')->orWhere('specific_roles', '=', '');
			foreach ($roles as $role)
				$q = $q->orWhere('specific_roles', 'regexp', '[[:<:]]' . $role . '[[:>:]]');// NOTE: MySQL RegExp specific!
		});
	}

	//endregion

	/**
	 * @param  array|\Traversable $roles Role names
	 * @return bool
	 */
	public function isInRoles($roles = array())
	{
		if (!($specificRoles = $this->specific_roles))
			return true;

		foreach ($roles as $role) {
			if (in_array($role, $specificRoles))
				return true;
		}
		return false;
	}

	//region Files list Methods

	const PDF_PATH = 'pdf/templates';

	/**
	 * @param  ThemeLayout $theme
	 * @return array|string[]
	 */
	public static function listPdfTemplateFiles($theme = null)
	{
		$list = array();
		if (!empty($theme)) {
			$filter = '/[^_.-]*' . preg_quote(preg_replace('/\d+$/', '', $theme->name), '/') . '|blank\b/i';

			if (!empty($theme->pdf_file))
				$list[$theme->pdf_file] = str_replace(static::PDF_PATH . '/', '', $theme->pdf_file);
		}

		$path = storage_path(static::PDF_PATH);
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::UNIX_PATHS));
		/* @var $file \SplFileInfo */
		foreach ($iterator as $file) {
			if ($file->isFile() && strcasecmp('pdf', $file->getExtension()) == 0
				&& (empty($filter) || preg_match($filter, $file->getFilename()))
			) {
				$fn = substr($file->getPathname(), strlen($path) + 1);
				$list[static::PDF_PATH . '/' . $fn] = $fn;
			}
		}

		ksort($list);
		return $list;
	}

	/**
	 * @return array|string[]
	 */
	public static function listGeneratorClasses()
	{
		$list = array();

		$path = app_path('services/PdfBuilder');
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::UNIX_PATHS));
		/* @var $file \SplFileInfo */
		foreach ($iterator as $file) {
			if ($file->isFile() && preg_match('/Generator\.php$/i', $file->getFilename())) {
				$list[] = substr($file->getPathname(), strlen($path) + 1, -4);
			}
		}

		sort($list);
		return array_combine($list, $list);
	}

	//endregion

}
