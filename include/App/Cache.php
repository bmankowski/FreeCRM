<?php
namespace App;

/**
 * Simple Cache implementation
 * Replaces the deprecated vendor2 cache classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Cache
{
    protected static $initialized = false;
    protected static $cacheDir = 'cache/';
    protected static $defaultTtl = 3600; // 1 hour
    
    // Cache duration constants
    const SHORT = 300;   // 5 minutes
    const MEDIUM = 1800; // 30 minutes
    const LONG = 3600;   // 1 hour

    /**
     * Initialize the cache system
     * @return void
     */
    public static function init()
    {
        if (static::$initialized) {
            return;
        }

        // Create cache directory if it doesn't exist
        if (!is_dir(static::$cacheDir)) {
            mkdir(static::$cacheDir, 0755, true);
        }

        static::$initialized = true;
    }

    /**
     * Get a value from cache
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        static::init();
        
        $file = static::getCacheFile($key);
        
        if (!file_exists($file)) {
            return $default;
        }
        
        $data = file_get_contents($file);
        $cached = unserialize($data);
        
        // Check if expired
        if ($cached['expires'] < time()) {
            unlink($file);
            return $default;
        }
        
        return $cached['value'];
    }

    /**
     * Set a value in cache
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live in seconds
     * @return bool
     */
    public static function set($key, $value, $ttl = null)
    {
        static::init();
        
        if ($ttl === null) {
            $ttl = static::$defaultTtl;
        }
        
        // Ensure TTL is an integer
        $ttl = (int) $ttl;
        
        $file = static::getCacheFile($key);
        $data = serialize([
            'value' => $value,
            'expires' => time() + $ttl
        ]);
        
        return file_put_contents($file, $data, LOCK_EX) !== false;
    }

    /**
     * Delete a value from cache
     * @param string $key
     * @return bool
     */
    public static function delete($key)
    {
        static::init();
        
        $file = static::getCacheFile($key);
        
        if (file_exists($file)) {
            return unlink($file);
        }
        
        return true;
    }

    /**
     * Clear all cache
     * @return bool
     */
    public static function clear()
    {
        static::init();
        
        $files = glob(static::$cacheDir . '*.cache');
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Check if a key exists in cache
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return static::get($key) !== null;
    }

    /**
     * Save data to cache (alias for set)
     * @param string $key
     * @param mixed $value
     * @param int $ttl Time to live in seconds
     * @return bool
     */
    public static function save($key, $value, $ttl = null)
    {
        return static::set($key, $value, $ttl);
    }

    /**
     * Get cache file path
     * @param string $key
     * @return string
     */
    protected static function getCacheFile($key)
    {
        return static::$cacheDir . md5($key) . '.cache';
    }
}
