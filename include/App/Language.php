<?php
namespace App;

/**
 * Simple Language class
 * Replaces the deprecated vendor2 language classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Language
{
    protected static $initialized = false;
    protected static $currentLanguage = 'en_us';
    protected static $translations = [];

    /**
     * Initialize the language system
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
     * Get current language
     * @return string
     */
    public static function getCurrentLanguage()
    {
        static::init();
        return static::$currentLanguage;
    }

    /**
     * Set current language
     * @param string $language
     * @return void
     */
    public static function setCurrentLanguage($language)
    {
        static::$currentLanguage = $language;
    }

    /**
     * Translate a key
     * @param string $key
     * @param array $params
     * @return string
     */
    public static function translate($key, $params = [])
    {
        static::init();
        
        // Simple translation - return the key for now
        // In a real implementation, this would load translation files
        $translation = static::$translations[$key] ?? $key;
        
        // Replace parameters if provided
        if (!empty($params) && is_array($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace('{' . $param . '}', $value, $translation);
            }
        }
        
        return $translation;
    }

    /**
     * Add translation
     * @param string $key
     * @param string $value
     * @return void
     */
    public static function addTranslation($key, $value)
    {
        static::$translations[$key] = $value;
    }

    /**
     * Load translations from file
     * @param string $file
     * @return void
     */
    public static function loadTranslations($file)
    {
        static::init();
        
        if (file_exists($file)) {
            $translations = include $file;
            if (is_array($translations)) {
                static::$translations = array_merge(static::$translations, $translations);
            }
        }
    }

    /**
     * Get all translations
     * @return array
     */
    public static function getAllTranslations()
    {
        static::init();
        return static::$translations;
    }
}
