<?php
namespace App;

/**
 * Simple User class
 * Replaces the deprecated vendor2 user classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class User
{
    protected static $currentUser = null;
    protected static $initialized = false;

    /**
     * Initialize the user system
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
     * Get current user
     * @return mixed
     */
    public static function getCurrentUser()
    {
        static::init();
        return static::$currentUser;
    }

    /**
     * Set current user
     * @param mixed $user
     * @return void
     */
    public static function setCurrentUser($user)
    {
        static::$currentUser = $user;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function isLoggedIn()
    {
        static::init();
        return static::$currentUser !== null;
    }

    /**
     * Get user ID
     * @return int|null
     */
    public static function getUserId()
    {
        static::init();
        return static::$currentUser ? static::$currentUser->id : null;
    }

    /**
     * Get user name
     * @return string|null
     */
    public static function getUserName()
    {
        static::init();
        return static::$currentUser ? static::$currentUser->name : null;
    }

    /**
     * Get current user model
     * @return \App\User
     */
    public static function getCurrentUserModel()
    {
        static::init();
        return new static();
    }

    /**
     * Get user detail
     * @param string $key
     * @return mixed
     */
    public function getDetail($key)
    {
        static::init();
        
        // Return default language for now
        if ($key === 'language') {
            return 'en_us';
        }
        
        return null;
    }
}
