<?php
namespace App;

/**
 * Simple RequestUtil class
 * Replaces the deprecated vendor2 request utility classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class RequestUtil
{
    protected static $initialized = false;

    /**
     * Initialize the request utility system
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
     * Get request method
     * @return string
     */
    public static function getRequestMethod()
    {
        static::init();
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get request URI
     * @return string
     */
    public static function getRequestUri()
    {
        static::init();
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    /**
     * Get request headers
     * @return array
     */
    public static function getHeaders()
    {
        static::init();
        return getallheaders() ?: [];
    }

    /**
     * Set response header
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function setHeader($name, $value)
    {
        static::init();
        header("{$name}: {$value}");
    }

    /**
     * Set multiple response headers
     * @param array $headers
     * @return void
     */
    public static function setHeaders(array $headers)
    {
        static::init();
        foreach ($headers as $name => $value) {
            static::setHeader($name, $value);
        }
    }

    /**
     * Get POST data
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getPost($key = null, $default = null)
    {
        static::init();
        
        if ($key === null) {
            return $_POST;
        }
        
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getGet($key = null, $default = null)
    {
        static::init();
        
        if ($key === null) {
            return $_GET;
        }
        
        return $_GET[$key] ?? $default;
    }

    /**
     * Get request data (POST or GET)
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getRequest($key = null, $default = null)
    {
        static::init();
        
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }
        
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Get browser information
     * @return array
     */
    public static function getBrowserInfo()
    {
        static::init();
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return [
            'user_agent' => $userAgent,
            'browser' => static::detectBrowser($userAgent),
            'version' => static::detectBrowserVersion($userAgent),
            'platform' => static::detectPlatform($userAgent),
            'is_mobile' => static::isMobile($userAgent),
            'https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'ie' => stripos($userAgent, 'MSIE') !== false || stripos($userAgent, 'Trident') !== false,
        ];
    }

    /**
     * Detect browser from user agent
     * @param string $userAgent
     * @return string
     */
    protected static function detectBrowser($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
            return 'Internet Explorer';
        }
        
        return 'Unknown';
    }

    /**
     * Detect browser version
     * @param string $userAgent
     * @return string
     */
    protected static function detectBrowserVersion($userAgent)
    {
        // Simple version detection
        if (preg_match('/(Chrome|Firefox|Safari|Edge)\/([0-9.]+)/', $userAgent, $matches)) {
            return $matches[2];
        }
        
        return 'Unknown';
    }

    /**
     * Detect platform from user agent
     * @param string $userAgent
     * @return string
     */
    protected static function detectPlatform($userAgent)
    {
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            return 'Mac';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            return 'iOS';
        }
        
        return 'Unknown';
    }

    /**
     * Check if device is mobile
     * @param string $userAgent
     * @return bool
     */
    protected static function isMobile($userAgent)
    {
        return strpos($userAgent, 'Mobile') !== false || 
               strpos($userAgent, 'Android') !== false || 
               strpos($userAgent, 'iPhone') !== false || 
               strpos($userAgent, 'iPad') !== false;
    }
}
