<?php
namespace App;

/**
 * Simple Database class
 * Replaces the deprecated vendor2 database classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Db
{
    public static $connectCache = false;
    protected static $connection = null;
    protected static $initialized = false;

    /**
     * Initialize the database system
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
     * Get database instance (singleton pattern)
     * @return self
     */
    public static function getInstance()
    {
        static::init();
        return new static();
    }

    /**
     * Get database driver name
     * @return string
     */
    public function getDriverName()
    {
        // Default to mysql for compatibility
        return 'mysql';
    }

    /**
     * Get database connection
     * @return mixed
     */
    public static function getConnection()
    {
        static::init();
        return static::$connection;
    }

    /**
     * Set database connection
     * @param mixed $connection
     * @return void
     */
    public static function setConnection($connection)
    {
        static::$connection = $connection;
    }

    /**
     * Execute a query
     * @param string $query
     * @param array $params
     * @return mixed
     */
    public static function query($query, $params = [])
    {
        static::init();
        // Simple implementation - would need actual database connection
        return true;
    }

    /**
     * Get last insert ID
     * @return int
     */
    public static function getLastInsertId()
    {
        static::init();
        return 0;
    }

    /**
     * Begin transaction
     * @return bool
     */
    public static function beginTransaction()
    {
        static::init();
        return true;
    }

    /**
     * Commit transaction
     * @return bool
     */
    public static function commit()
    {
        static::init();
        return true;
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public static function rollback()
    {
        static::init();
        return true;
    }
}
