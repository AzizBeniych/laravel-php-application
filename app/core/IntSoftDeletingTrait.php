<?php

/**
 * @property bool $exists
 *
 * @method static void addGlobalScope(\Illuminate\Database\Eloquent\ScopeInterface $scope)
 * @method static \Eloquent where($column, $operator = null, $value = null, $boolean = 'and')
 *
 * @method \Illuminate\Database\Eloquent\Builder newQuery()
 * @method string getKeyName()
 * @method mixed getKey()
 * @method \Carbon\Carbon freshTimestamp()
 * @method mixed fireModelEvent($event, $halt = true)
 * @method bool save(array $options = array())
 * @method \Illuminate\Database\Eloquent\Builder newQueryWithoutScope(\Illuminate\Database\Eloquent\ScopeInterface $scope)
 * @method bool usesTimestamps()
 * @method string getUpdatedAtColumn()
 */
trait IntSoftDeletingTrait
{
	use \Illuminate\Database\Eloquent\SoftDeletingTrait;

	/**
	 * {@inheritDoc}
	 */
	public static function bootSoftDeletingTrait()
	{
		static::addGlobalScope(new IntSoftDeletingScope);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function runSoftDelete()
	{
		$query = $this->newQuery()->where($this->getKeyName(), $this->getKey());

		$column = $this->getDeletedAtColumn();
		$this->{$column} = $time = $this->freshTimestamp();

		// preserve updated_at
		$values = array($column => $time->getTimestamp());//$this->fromDateTime
		if ($this->usesTimestamps()) {
			$col = $this->getUpdatedAtColumn();
			$values += array($col => \DB::raw($col));
		}

		$query->update($values);
	}

	/**
	 * {@inheritDoc}
	 */
	public function restore()
	{
		// If the restoring event does not return false, we will proceed with this
		// restore operation. Otherwise, we bail out so the developer will stop
		// the restore totally. We will clear the deleted timestamp and save.
		if ($this->fireModelEvent('restoring') === false) {
			return false;
		}

		$this->{$this->getDeletedAtColumn()} = 0;

		// Once we have saved the model, we will fire the "restored" event so this
		// developer will do anything they need to after a restore operation is
		// totally finished. Then we will return the result of the save call.
		$this->exists = true;

		$result = $this->save();
		$this->fireModelEvent('restored', false);

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function trashed()
	{
		return !empty($this->{$this->getDeletedAtColumn()});
	}

	/**
	 * {@inheritDoc}
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public static function withTrashed()
	{
		return (new static)->newQueryWithoutScope(new IntSoftDeletingScope);
	}

	/**
	 * {@inheritDoc}
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public static function onlyTrashed()
	{
		$instance = new static;
		/* @var $instance \IntSoftDeletingTrait */
		$column = $instance->getQualifiedDeletedAtColumn();

		return $instance->newQueryWithoutScope(new IntSoftDeletingScope)->where($column, '>', DB::raw(0));
	}

	/**
	 * {@inheritDoc}
	 * @param bool $withTrashed
	 * @return \Illuminate\Database\Eloquent\Model|static
	 */
	public static function firstOrNew(array $attributes, $withTrashed = false)
	{
		if (!$withTrashed) {
			$instance = static::where($attributes)->first();
		} else {
			$instance = static::withTrashed()->where($attributes)->first();
		}

		return is_null($instance) ? new static($attributes) : $instance;
	}

	public static function deleteMany($ids, $columns = array('id', 'deleted_at', \Eloquent::UPDATED_AT))
	{
		if ($ids instanceof \Illuminate\Support\Collection) {
			$list = $ids;
		} else {
			$list = static::withTrashed()->findMany(is_array($ids) ? $ids : array($ids), $columns);
		}

		$results = array();
		DB::beginTransaction();
		try {
			foreach ($list as $obj) {
				/* @var $obj \IntSoftDeletingTrait|\Illuminate\Database\Eloquent\Model */
				$id = $obj->getKey();

				if ($obj->trashed()) {
					$obj->forceDelete();
					$results[$id] = false;

				} elseif ($obj->delete()) {
					$results[$id] = true;
				}
			}
			DB::commit();

		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}

		return $results;
	}

}
