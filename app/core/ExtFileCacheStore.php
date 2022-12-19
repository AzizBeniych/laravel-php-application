<?php

class ExtFileCacheStore extends \Illuminate\Cache\FileStore
{

	/**
	 * @var array
	 */
	protected $lottery;

	/**
	 * {@inheritDoc}
	 */
	public function __construct(\Illuminate\Filesystem\Filesystem $files, $directory, array $lottery = null)
	{
		parent::__construct($files, $directory);
		$this->lottery = $lottery;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getPayload($key)
	{
		$path = $this->path($key);
		$files = $this->files->glob($path . '.*', GLOB_NOSORT);

		// if the file doesn't exists
		if (!empty($files)) {
			$path = array_pop($files);
			$expire = static::getFileExpiration($path);

			// is expired ?
			if (time() >= $expire) {
				array_push($files, $path);
				$this->files->delete($files);//$this->forget($key);

			} else {
				if (count($files) > 0)
					$this->files->delete($files);

				// number of minutes that are remaining for cache
				$time = ceil(($expire - time()) / 60);
				try {
					$data = unserialize($this->files->get($path));
					return compact('data', 'time');

				} catch (\Exception $e) {
					// do nothing
				}
			}
		}

		return array('data' => null, 'time' => null);
	}

	/**
	 * {@inheritDoc}
	 */
	public function put($key, $value, $minutes)
	{
		$file = $this->path($key) . '.' . $this->expiration($minutes);
		$this->createCacheDirectory($file);

		$value = serialize($value);
		$this->files->put($file, $value);

		// delete all the expired caches
		if (!empty($this->lottery) && mt_rand(1, $this->lottery[1]) <= $this->lottery[0])
			$this->collectGarbage();
	}

	/**
	 * {@inheritDoc}
	 */
	public function forget($key)
	{
		$file = $this->path($key);
		$this->files->delete($this->files->glob($file . '.*', GLOB_NOSORT));
	}

	/**
	 * {@inheritDoc}
	 */
	public function flush()
	{
		if ($this->files->isDirectory($this->directory)) {
			foreach ($this->files->glob($this->directory . '/??', GLOB_NOSORT) as $directory) {
				$this->files->deleteDirectory($directory);
			}
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function path($key)
	{
		$hash = md5($key);
		return $this->directory . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash;
	}

	protected static function getFileExpiration($path)
	{
		return substr($path, strrpos($path, '.') + 1);
	}

	protected function collectGarbage()
	{
		if ($this->files->isDirectory($this->directory)) {
			$paths = $this->files->glob($this->directory . '/??/??/*.*', GLOB_NOSORT);

			if (!empty($paths)) {
				$paths = array_filter($paths, function ($path) {
					return (time() >= static::getFileExpiration($path));
				});

				if (count($paths) > 0)
					$this->files->delete($paths);
			}
		}
	}

}
