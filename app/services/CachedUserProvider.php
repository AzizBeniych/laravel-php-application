<?php

class CachedUserProvider extends \Illuminate\Auth\EloquentUserProvider
{
	/** @var \Illuminate\Cache\Repository */
	protected $cache;
	/** @var int */
	protected $lifetime;

	public function __construct(
		\Illuminate\Hashing\HasherInterface $hasher, $model,
		\Illuminate\Cache\Repository $cache, $lifetime)
	{
		parent::__construct($hasher, $model);

		$this->cache = $cache;
		$this->lifetime = $lifetime;
	}

	/**
	 * {@inheritDoc}
	 */
	public function retrieveById($identifier)
	{
		$cacheKey = static::cacheKey($identifier);

		return $this->cache->remember($cacheKey, $this->lifetime, function () use ($identifier) {
			//return parent::retrieveById($identifier);
			return $this->createModel()->newQuery()->with('agencies')->with('roles')->find($identifier);
		});
	}

	public static function cacheKey($identifier)
	{
		return 'user-' . (is_array($identifier) ? implode('_', $identifier) : $identifier);
	}

	/**
	 * {@inheritDoc}
	 */
	public function retrieveByCredentials(array $credentials)
	{
		// First we will add each credential element to the query as a where clause.
		$query = $this->createModel()->newQuery();

		foreach ($credentials as $key => $value) {
			if (empty($key) && $value instanceof \Closure) {
				$query->where($value);
			} elseif (!str_contains($key, 'password')) {
				$query->where($key, $value);
			}
		}

		return $query->first();
	}

}
