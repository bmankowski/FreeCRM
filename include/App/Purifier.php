<?php
namespace App;

/**
 * Simple Purifier class
 * Replaces the deprecated vendor2 purifier classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Purifier
{
    protected static $initialized = false;

    /**
     * Initialize the purifier system
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
     * Generic purify method - delegates to appropriate specific method
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    public static function purify($value, $type = 'text')
    {
        static::init();
        
        switch ($type) {
            case 'html':
                return static::purifyHtml($value);
            case 'url':
                return static::purifyUrl($value);
            case 'email':
                return static::purifyEmail($value);
            case 'integer':
                return static::purifyInteger($value);
            case 'float':
                return static::purifyFloat($value);
            case 'boolean':
                return static::purifyBoolean($value);
            case 'text':
            default:
                return static::purifyText($value);
        }
    }

    /**
     * Purify HTML content
     * @param string $html
     * @param string $config
     * @return string
     */
    public static function purifyHtml($html, $config = 'default')
    {
        static::init();
        
        // Simple HTML purification - remove dangerous tags and attributes
        $html = strip_tags($html, '<p><br><strong><em><ul><ol><li><a><img><div><span><h1><h2><h3><h4><h5><h6>');
        
        // Remove dangerous attributes
        $html = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/', '', $html);
        $html = preg_replace('/\s*javascript\s*:/i', '', $html);
        
        return $html;
    }

    /**
     * Purify text content
     * @param string $text
     * @return string
     */
    public static function purifyText($text)
    {
        static::init();
        
        // Simple text purification
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Purify URL
     * @param string $url
     * @return string
     */
    public static function purifyUrl($url)
    {
        static::init();
        
        // Simple URL validation and purification
        $url = trim($url);
        
        // Remove javascript: and data: protocols
        if (preg_match('/^(javascript|data):/i', $url)) {
            return '#';
        }
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^#/', $url)) {
            return '#';
        }
        
        return $url;
    }

    /**
     * Purify email
     * @param string $email
     * @return string
     */
    public static function purifyEmail($email)
    {
        static::init();
        
        // Simple email validation and purification
        $email = trim($email);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        
        return $email;
    }

    /**
     * Purify integer
     * @param mixed $value
     * @return int
     */
    public static function purifyInteger($value)
    {
        static::init();
        
        return (int) $value;
    }

    /**
     * Purify float
     * @param mixed $value
     * @return float
     */
    public static function purifyFloat($value)
    {
        static::init();
        
        return (float) $value;
    }

    /**
     * Purify boolean
     * @param mixed $value
     * @return bool
     */
    public static function purifyBoolean($value)
    {
        static::init();
        
        return (bool) $value;
    }

    /**
     * Decode HTML entities
     * @param string $html
     * @return string
     */
    public static function decodeHtml($html)
    {
        static::init();
        
        return html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Encode HTML entities
     * @param string $html
     * @return string
     */
    public static function encodeHtml($html)
    {
        static::init();
        
        return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Purify SQL content
     * @param mixed $value
     * @param bool $skipEmpty
     * @return mixed
     */
    public static function purifySql($value, $skipEmpty = true)
    {
        static::init();
        
        if ($skipEmpty && empty($value)) {
            return $value;
        }
        
        // Basic SQL injection prevention
        $value = str_replace(['\'', '"', ';', '--', '/*', '*/'], '', $value);
        
        return $value;
    }
}
