<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

/**
 * WebUI Error Handler
 * 
 * Converts PHP errors to exceptions for better error handling.
 * Provides logging and display capabilities for application errors.
 * 
 * @package Main
 */


namespace FreeCRM\EntryPoint;

use FreeCRM\AppConfig;
use App\Debugger;

class WebUI_ErrorHandler
{
    /**
     * Handle PHP errors by logging and optionally displaying them
     * 
     * @param int $errno Error number
     * @param string $errstr Error message
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return bool False to let PHP handle the error normally
     */
    public static function handle($errno, $errstr, $errfile, $errline)
    {
        // Skip if output buffering is active to avoid conflicts
        if (ob_get_level() > 0) {
            return false;
        }

        $message = sprintf('%d: %s in %s, line %d', $errno, $errstr, $errfile, $errline);
        
        self::logError($message);
        self::displayError($message);

        return false; // Let PHP handle the error normally
    }

    /**
     * Log error to file
     * 
     * @param string $message Error message
     */
    private static function logError($message)
    {
        if (!AppConfig::debug('EXCEPTION_ERROR_TO_FILE')) {
            return;
        }
        
        $content = $message . PHP_EOL . Debugger::getBacktrace() . PHP_EOL;
        @file_put_contents(
            'cache/logs/system.log',
            $content,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Display error to user
     * 
     * @param string $message Error message
     */
    private static function displayError($message)
    {
        if (AppConfig::debug('EXCEPTION_ERROR_TO_SHOW')) {
            \vtlib\Functions::throwNewException($message, false);
        }
    }

    /**
     * Register the error handler
     * 
     * @return void
     */
    public static function register()
    {
        set_error_handler(
            [self::class, 'handle'],
            AppConfig::debug('EXCEPTION_ERROR_LEVEL')
        );
    }
}

