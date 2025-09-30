<?php
namespace App;

/**
 * Simple Log class
 * Replaces the deprecated vendor2 logging classes
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Log
{
    public static $logToProfile = false;
    public static $logToConsole = false;
    public static $logToFile = false;
    protected static $initialized = false;
    protected static $logFile = 'logs/app.log';

    /**
     * Initialize the logging system
     * @return void
     */
    public static function init()
    {
        if (static::$initialized) {
            return;
        }

        // Create logs directory if it doesn't exist
        $logDir = dirname(static::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        static::$initialized = true;
    }

    /**
     * Log a message
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log($level, $message, array $context = [])
    {
        static::init();
        
        // Check if this level should be logged
        $logLevels = \AppConfig::debug('LOG_LEVELS');
        if ($logLevels !== false && !in_array(strtolower($level), $logLevels)) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_PRETTY_PRINT) : '';
        $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;
        
        if (static::$logToFile) {
            file_put_contents(static::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        if (static::$logToConsole) {
            echo $logEntry;
        }
        
        // Also log to debug system
        \App\Debugger::log($message, array_merge(['level' => $level], $context));
    }

    /**
     * Log debug message
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function debug($message, array $context = [])
    {
        static::log('DEBUG', $message, $context);
    }

    /**
     * Log trace message
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function trace($message, array $context = [])
    {
        static::log('TRACE', $message, $context);
    }

    /**
     * Log info message
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function info($message, array $context = [])
    {
        static::log('INFO', $message, $context);
    }

    /**
     * Log warning message
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function warning($message, array $context = [])
    {
        static::log('WARNING', $message, $context);
    }

    /**
     * Log error message
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function error($message, array $context = [])
    {
        static::log('ERROR', $message, $context);
    }

    /**
     * Log critical message
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function critical($message, array $context = [])
    {
        static::log('CRITICAL', $message, $context);
    }

    /**
     * Log SQL query
     * @param string $query
     * @param array $params
     * @param float $executionTime
     * @return void
     */
    public static function sql($query, array $params = [], $executionTime = null)
    {
        $context = [
            'query' => $query,
            'params' => $params,
            'execution_time' => $executionTime
        ];
        
        static::log('SQL', "Query executed", $context);
    }

    /**
     * Log performance metrics
     * @param string $operation
     * @param float $executionTime
     * @param int $memoryUsage
     * @return void
     */
    public static function performance($operation, $executionTime, $memoryUsage = null)
    {
        $context = [
            'operation' => $operation,
            'execution_time' => $executionTime,
            'memory_usage' => $memoryUsage ?: memory_get_usage(true)
        ];
        
        static::log('PERFORMANCE', "Operation: {$operation}", $context);
    }

    /**
     * Get log file path
     * @return string
     */
    public static function getLogFile()
    {
        return static::$logFile;
    }

    /**
     * Set custom log file
     * @param string $logFile
     * @return void
     */
    public static function setLogFile($logFile)
    {
        static::$logFile = $logFile;
    }

    /**
     * Clear log file
     * @return bool
     */
    public static function clearLog()
    {
        if (file_exists(static::$logFile)) {
            return file_put_contents(static::$logFile, '') !== false;
        }
        return true;
    }

    /**
     * Get log file size
     * @return int
     */
    public static function getLogSize()
    {
        return file_exists(static::$logFile) ? filesize(static::$logFile) : 0;
    }

    /**
     * Get last N lines from log file
     * @param int $lines
     * @return array
     */
    public static function getLastLines($lines = 50)
    {
        if (!file_exists(static::$logFile)) {
            return [];
        }
        
        $file = file(static::$logFile);
        return array_slice($file, -$lines);
    }
}
