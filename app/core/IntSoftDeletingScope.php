<?php

class IntSoftDeletingScope extends \Illuminate\Database\Eloquent\SoftDeletingScope
{

	/**
	 * {@inheritDoc}
	 */
	public function apply(\Illuminate\Database\Eloquent\Builder $builder, $model = null)
	{
		if (!isset($model))
			$model = $builder->getModel();

		/* @var $model \IntSoftDeletingTrait */
		$builder->where($model->getQualifiedDeletedAtColumn(), '=', DB::raw(0));

		$this->extend($builder);
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove(\Illuminate\Database\Eloquent\Builder $builder, $model = null)
	{
		if (!isset($model))
			$model = $builder->getModel();

		/* @var $model \IntSoftDeletingTrait */
		$column = $model->getQualifiedDeletedAtColumn();

		$query = $builder->getQuery();
		foreach ((array)$query->wheres as $key => $where) {
			// If the where clause is a soft delete date constraint, we will remove it from
			// the query and reset the keys on the wheres. This allows this developer to
			// include deleted model in a relationship result set that is lazy loaded.
			if ($this->isSoftDeleteConstraint($where, $column)) {
				unset($query->wheres[$key]);

				$query->wheres = array_values($query->wheres);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function extend(\Illuminate\Database\Eloquent\Builder $builder)
	{
		foreach ($this->extensions as $extension) {
			$this->{"add{$extension}"}($builder);
		}

		$builder->onDelete(function (\Illuminate\Database\Eloquent\Builder $builder) {
			$column = $this->getDeletedAtColumn($builder);

			$model = $builder->getModel();
			$values = array(
				$column => $model->freshTimestamp()->getTimestamp()//freshTimestampString
			);

			// preserve updated_at
			if ($model->usesTimestamps()) {
				$col = $model->getUpdatedAtColumn();
				$values += array($col => \DB::raw($col));
			}

			return $builder->update($values);
		});
	}

	/**
	 * {@inheritDoc}
	 */
	protected function addRestore(\Illuminate\Database\Eloquent\Builder $builder)
	{
		$builder->macro('restore', function (\Illuminate\Database\Eloquent\Builder $builder) {
			/* @var $builder \Illuminate\Database\Eloquent\Builder|\IntSoftDeletingTrait */
			$builder->withTrashed();

			/* @var $model \IntSoftDeletingTrait */
			$model = $builder->getModel();
			return $builder->update(array($model->getDeletedAtColumn() => 0));
		});
	}

	/**
	 * {@inheritDoc}
	 */
	protected function addOnlyTrashed(\Illuminate\Database\Eloquent\Builder $builder)
	{
		$builder->macro('onlyTrashed', function (\Illuminate\Database\Eloquent\Builder $builder) {
			$this->remove($builder);

			/* @var $model \IntSoftDeletingTrait */
			$model = $builder->getModel();
			$builder->getQuery()->where($model->getQualifiedDeletedAtColumn(), '>', DB::raw(0));

			return $builder;
		});
	}

	/**
	 * {@inheritDoc}
	 */
	protected function isSoftDeleteConstraint(array $where, $column)
	{
		return $where['type'] == 'Basic' && $where['column'] == $column && $where['operator'] == '=' && (string)$where['value'] === '0';
	}

}
