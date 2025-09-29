<?php
namespace App;

/**
 * Simple Version class
 * Replaces the deprecated vendor2 version classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Version
{
    protected static $initialized = false;
    protected static $version = '1.0.0';
    protected static $build = '1';

    /**
     * Initialize the version system
     * @return void
     */
    public static function init()
    {
        if (static::$initialized) {
            return;
        }

        static::$initialized = true;
    }

    /**
     * Get application version
     * @return string
     */
    public static function getVersion()
    {
        static::init();
        return static::$version;
    }

    /**
     * Get build number
     * @return string
     */
    public static function getBuild()
    {
        static::init();
        return static::$build;
    }

    /**
     * Get full version string
     * @return string
     */
    public static function getFullVersion()
    {
        static::init();
        return static::$version . '.' . static::$build;
    }

    /**
     * Set version
     * @param string $version
     * @return void
     */
    public static function setVersion($version)
    {
        static::$version = $version;
    }

    /**
     * Set build number
     * @param string $build
     * @return void
     */
    public static function setBuild($build)
    {
        static::$build = $build;
    }

    /**
     * Compare versions
     * @param string $version1
     * @param string $version2
     * @return int
     */
    public static function compare($version1, $version2)
    {
        return version_compare($version1, $version2);
    }

    /**
     * Check if version is newer
     * @param string $version
     * @return bool
     */
    public static function isNewer($version)
    {
        static::init();
        return static::compare($version, static::$version) > 0;
    }

    /**
     * Check if version is older
     * @param string $version
     * @return bool
     */
    public static function isOlder($version)
    {
        static::init();
        return static::compare($version, static::$version) < 0;
    }

    /**
     * Get version information
     * @param string $key
     * @return mixed
     */
    public static function get($key = null)
    {
        static::init();
        
        if ($key === null) {
            return [
                'version' => static::$version,
                'build' => static::$build,
                'full' => static::getFullVersion(),
            ];
        }
        
        switch ($key) {
            case 'version':
                return static::$version;
            case 'build':
                return static::$build;
            case 'full':
                return static::getFullVersion();
            default:
                return null;
        }
    }
}
