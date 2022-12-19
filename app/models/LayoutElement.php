<?php

/**
 * @property int $id
 *
 * @property string $name
 * @property string $input_type
 * @property string $caption        Placeholder
 * @property string $description    Setting-name for option values -or- Checkbox value - label pairs.
 *
 * @property string $tab
 * @property string $tab_header
 * @property string $category
 *
 * @property string $attributes     JSON
 * @property string $help_text
 *
 * @property string $label
 * @property int $label_width
 *
 * @property string $css_class      It's never used
 * @property int $col_width
 * @property string $wrapper_css
 * @property bool $is_inline
 *
 * @property string $validation     JSON?
 * @property string $error_placement
 *
 * @property string $profile_field
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 *
 * @property-read \Illuminate\Database\Eloquent\Relations\Pivot|object $pivot   Virtual property!
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|ThemeLayout[] $themes
 * @property-read \Illuminate\Database\Eloquent\Collection|UserElementValue[] $userValues
 *
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutElement whereId($id)
 * @method static \Illuminate\Database\Eloquent\Builder|LayoutElement whereName($name)
 */
class LayoutElement extends \Eloquent
{
	use IntSoftDeletingTrait;

	//region Constants

	public static $INPUT_TYPES = array(
		'text' => 'text-width',
		'select' => 'triangle-bottom',
		'radio' => 'record',
		'checkbox' => 'check',
		'textarea' => 'modal-window',
		'email' => 'envelope',
		'tel' => 'phone-alt',
		'url' => 'globe',
		'number' => 'sound-5-1',
		'date' => 'calendar',
		'time' => 'calendar',
		'datetime' => 'calendar',
		'file' => 'open',
		'radio_image' => 'picture'
	);
	public static $ERROR_PLACEMENTS = array('below', 'newline', 'above');

	//endregion

	const TABLE = 'layout_elements';
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
	protected $hidden = array(self::DELETED_AT, 'themes', 'userValues');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id', self::DELETED_AT);

	//region Relationships

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Eloquent\Builder|ThemeLayout
	 */
	public function themes()
	{
		return $this->belongsToMany('ThemeLayout', 'theme_layout_elements', 'element_id', 'layout_id')
			->withPivot('order');
	}

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Builder|UserElementValue
	 */
	public function userValues()
	{
		return $this->hasMany('UserElementValue', 'element_id');
	}

	//endregion

	//region Mutator Properties

	// TODO: attributes JSON

	//endregion

}
