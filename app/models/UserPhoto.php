<?php

/**
 * @property int $id
 * @property int $user_id
 *
 * @property string $filename
 * @property string $type
 *
 * @property string $mime_type
 * @property bool $is_processed
 * @property bool $has_thumbnail
 * @property \Carbon\Carbon $active_at
 *
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property bool $is_selected  Virtual field!
 * @property-read User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder|UserPhoto whereId($id)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPhoto whereUserId($userId)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPhoto whereType($type)
 * @method static \Illuminate\Database\Eloquent\Builder|UserPhoto whereTypeAndFilename($type, $filename)
 */
class UserPhoto extends \Eloquent
{
	//region Constants

	const TYPE_VERTICAL = 'vert';
	const TYPE_HORIZONTAL = 'horz';
	const TYPE_LOGO = 'logo';
	const TYPE_SIGNATURE = 'sign';

	public static $TYPES = array(self::TYPE_VERTICAL, self::TYPE_HORIZONTAL, self::TYPE_LOGO, self::TYPE_SIGNATURE);

	//endregion

	const TABLE = 'user_photos';

	/**
	 * The database table used by the model.
	 */
	protected $table = self::TABLE;

	/**
	 * The attributes that should be mutated to dates.
	 */
	protected $dates = array('active_at');

	/**
	 * The attributes excluded from the model's JSON form.
	 */
	protected $hidden = array('is_selected', 'user');

	/**
	 * The attributes that are not mass assignable.
	 */
	protected $guarded = array('id'/*, 'user_id'*/);

	//region Relationships

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|\Illuminate\Database\Eloquent\Builder|User
	 */
	public function user()
	{
		return $this->belongsTo('User', 'user_id');
	}

	//endregion

	//region Mutator Properties

	protected $_selected;

	public function getIsSelectedAttribute()
	{
		return $this->_selected;
	}

	/**
	 * @param  bool $value
	 */
	public function setIsSelectedAttribute($value)
	{
		$this->_selected = $value;
	}

	//endregion

	//region Image files Management

	const BASE_PATH = 'files/gallery/user';
	const THUMB_DIR = 'thumb';
	const PROCESS_DIR = 'proc';
	const DEFAULT_EXT = '.jpg';

	/**
	 * @param  string $filename
	 * @param  int $userId
	 * @param  string $photoType
	 * @return string
	 */
	public static function getPhotoPath($filename, $userId, $photoType = self::TYPE_HORIZONTAL)
	{
		return static::BASE_PATH . '/' . $photoType . '/' . $userId . '/' . $filename;
	}

	/**
	 * @param  array $files
	 * @param  string $default
	 * @return string
	 */
	protected function _getAvailablePhoto(array $files, $default = '')
	{
		foreach ($files as $fn) {
			if (empty($fn))
				continue;

			$path = static::getPhotoPath($fn, $this->user_id, $this->type);
			if (!function_exists('public_path') || file_exists(public_path($path)))
				return $path;
		}

		return $default;
	}

	/**
	 * @return string
	 */
	public function getProcessedFilename()
	{
		return /*!$this->is_processed ? null : */
			(static::PROCESS_DIR . '/' . $this->filename . static::DEFAULT_EXT);
	}

	/**
	 * @return string
	 */
	public function getPreviewFilename()
	{
		return !$this->has_thumbnail ? null :
			(static::THUMB_DIR . '/' . $this->filename . static::DEFAULT_EXT);
	}

	/**
	 * @param  string $default
	 * @return string
	 */
	public function getProcessedPhoto($default = '')
	{
		return $this->_getAvailablePhoto(array($this->getProcessedFilename(), $this->filename), $default);
	}

	/**
	 * @param  string $default
	 * @return string
	 */
	public function getPreviewPhoto($default = '')
	{
		return $this->_getAvailablePhoto(array($this->getPreviewFilename(), $this->filename), $default);
	}

	public function removeAllFiles()
	{
		foreach (array($this->getProcessedFilename(), $this->getPreviewFilename(), $this->filename) as $fn) {
			if (empty($fn))
				continue;

			$path = public_path(static::getPhotoPath($fn, $this->user_id, $this->type));
			if (file_exists($path))
				@unlink($path);
		}
	}

	//endregion

}
