<?php
namespace App;

/**
 * Simple Json class
 * Replaces the deprecated vendor2 JSON utility classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Json
{
    protected static $initialized = false;

    /**
     * Initialize the JSON system
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
     * Encode data to JSON
     * @param mixed $data
     * @param int $options
     * @return string
     */
    public static function encode($data, $options = 0)
    {
        static::init();
        
        return json_encode($data, $options);
    }

    /**
     * Decode JSON to data
     * @param string $json
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return mixed
     */
    public static function decode($json, $assoc = true, $depth = 512, $options = 0)
    {
        static::init();
        
        return json_decode($json, $assoc, $depth, $options);
    }

    /**
     * Check if string is valid JSON
     * @param string $string
     * @return bool
     */
    public static function isValid($string)
    {
        static::init();
        
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get last JSON error
     * @return string
     */
    public static function getLastError()
    {
        static::init();
        
        return json_last_error_msg();
    }

    /**
     * Pretty print JSON
     * @param mixed $data
     * @return string
     */
    public static function prettyPrint($data)
    {
        static::init();
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Escape JSON string
     * @param string $string
     * @return string
     */
    public static function escape($string)
    {
        static::init();
        
        return json_encode($string, JSON_UNESCAPED_UNICODE);
    }
}
