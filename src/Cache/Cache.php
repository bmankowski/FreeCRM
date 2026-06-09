<?php
namespace App\Cache;

/**
 * Unified Cache Class
 * @package FreeCRM.App.Cache
 * @license licenses/License.html
 */
class Cache
{
	/** @var Base|Apcu|XCache Cache driver instance */
	public static $pool;

	const LONG = 3600;
	const MEDIUM = 300;
	const SHORT = 60;

	/**
	 * Initialize cache class with configured driver
	 */
	public static function init()
	{
		$driver = \App\Core\AppConfig::performance('CACHING_DRIVER');
		if ($driver) {
			$className = '\App\Cache\\' . $driver;
			static::$pool = new $className();
			return;
		}
		static::$pool = new \App\Cache\Base();
	}

	/**
	 * Returns a Cache Item representing the specified key
	 * @param string $nameSpace Cache namespace
	 * @param string|int|array $key Cache ID
	 * @return mixed Cached value or false if not found
	 */
	public static function get($nameSpace, $key)
	{
		$cacheKey = is_array($key) ? $nameSpace . '-' . md5(serialize($key)) : "$nameSpace-$key";
		return static::$pool->get($cacheKey);
	}

	/**
	 * Confirms if the cache contains specified cache item
	 * @param string $nameSpace Cache namespace
	 * @param string|int|array $key Cache ID
	 * @return bool
	 */
	public static function has($nameSpace, $key)
	{
		$cacheKey = is_array($key) ? $nameSpace . '-' . md5(serialize($key)) : "$nameSpace-$key";
		return static::$pool->has($cacheKey);
	}

	/**
	 * Cache Save
	 * @param string $nameSpace Cache namespace
	 * @param string|int|array $key Cache ID
	 * @param mixed $value Data to store, supports string, array, objects
	 * @param int $duration Cache TTL (in seconds)
	 * @return bool
	 */
	public static function save($nameSpace, $key, $value = null, $duration = self::MEDIUM)
	{
		$cacheKey = is_array($key) ? $nameSpace . '-' . md5(serialize($key)) : "$nameSpace-$key";
		return static::$pool->save($cacheKey, $value, $duration);
	}

	/**
	 * Removes the item from the cache
	 * @param string $nameSpace Cache namespace
	 * @param string|int|array $key Cache ID
	 * @return bool
	 */
	public static function delete($nameSpace, $key)
	{
		$cacheKey = is_array($key) ? $nameSpace . '-' . md5(serialize($key)) : "$nameSpace-$key";
		static::$pool->delete($cacheKey);
	}

	/**
	 * Deletes all items in the cache
	 * @return bool
	 */
	public static function clear()
	{
		static::$pool->clear();
	}

	/**
	 * Clears all cache entries for a specific namespace
	 * @param string $nameSpace Cache namespace
	 * @return bool
	 */
	public static function clearNamespace($nameSpace)
	{
		// Note: This is a basic implementation
		// For production, consider storing namespace keys separately
		// For now, just clear all cache
		return static::clear();
	}
}

