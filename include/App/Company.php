<?php
namespace App;

/**
 * Simple Company class
 * Replaces the deprecated vendor2 company classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Company
{
    protected static $initialized = false;
    protected static $companyInfo = [];

    /**
     * Initialize the company system
     * @return void
     */
    public static function init()
    {
        if (static::$initialized) {
            return;
        }

        // Set default company information
        static::$companyInfo = [
            'name' => 'YetiForce',
            'website' => 'https://yetiforce.com',
            'email' => 'info@yetiforce.com',
            'phone' => '',
            'address' => '',
            'logo' => '',
        ];

        static::$initialized = true;
    }

    /**
     * Get company information
     * @param string $key
     * @return mixed
     */
    public static function get($key = null)
    {
        static::init();
        
        if ($key === null) {
            return static::$companyInfo;
        }
        
        return static::$companyInfo[$key] ?? null;
    }

    /**
     * Set company information
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value)
    {
        static::init();
        static::$companyInfo[$key] = $value;
    }

    /**
     * Get company name
     * @return string
     */
    public static function getName()
    {
        static::init();
        return static::$companyInfo['name'];
    }

    /**
     * Get company website
     * @return string
     */
    public static function getWebsite()
    {
        static::init();
        return static::$companyInfo['website'];
    }

    /**
     * Get company email
     * @return string
     */
    public static function getEmail()
    {
        static::init();
        return static::$companyInfo['email'];
    }

    /**
     * Get company logo
     * @return string
     */
    public static function getLogo()
    {
        static::init();
        return static::$companyInfo['logo'];
    }

    /**
     * Set company logo
     * @param string $logo
     * @return void
     */
    public static function setLogo($logo)
    {
        static::init();
        static::$companyInfo['logo'] = $logo;
    }

    /**
     * Get company instance by ID
     * @param int $id
     * @return \App\Company
     */
    public static function getInstanceById($id = 1)
    {
        static::init();
        return new static();
    }

    /**
     * Get company ID
     * @return int
     */
    public function getId()
    {
        return 1; // Default company ID
    }

    /**
     * Get company name (instance method)
     * @return string
     */
    public function getCompanyName()
    {
        return static::getName();
    }

    /**
     * Get company website (instance method)
     * @return string
     */
    public function getCompanyWebsite()
    {
        return static::getWebsite();
    }

    /**
     * Get company email (instance method)
     * @return string
     */
    public function getCompanyEmail()
    {
        return static::getEmail();
    }
}
