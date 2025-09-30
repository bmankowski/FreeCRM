<?php
namespace App;

// Ensure composer autoloader is loaded
if (!class_exists('Symfony\Component\VarDumper\VarDumper')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Modern Debugger class using Symfony VarDumper
 * Enhanced with comprehensive debugging features
 * 
 * @package YetiForce.App
 * @license licenses/License.html
 */
class Debugger
{
    protected static $initialized = false;
    protected static $logger = null;
    protected static $debugInfo = [];
    protected static $startTime = null;
    protected static $memoryStart = null;

    /**
     * Initialize the modern debugging system
     * @return void
     */
    public static function init()
    {
        if (static::$initialized) {
            return;
        }

        // Set start time and memory usage
        static::$startTime = microtime(true);
        static::$memoryStart = memory_get_usage(true);

        // Configure VarDumper for better output
        VarDumper::setHandler(function ($var) {
            $cloner = new VarCloner();
            $dumper = 'cli' === PHP_SAPI ? new CliDumper() : new HtmlDumper();
            $dumper->dump($cloner->cloneVar($var));
        });

        // Set up error reporting based on debug config
        if (\AppConfig::debug('EXCEPTION_ERROR_HANDLER')) {
            set_error_handler([static::class, 'errorHandler']);
            set_exception_handler([static::class, 'exceptionHandler']);
            register_shutdown_function([static::class, 'shutdownHandler']);
        }

        // Set error reporting level
        error_reporting(\AppConfig::debug('EXCEPTION_ERROR_LEVEL'));

        static::$initialized = true;
    }

    /**
     * Custom error handler
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     * @return bool
     */
    public static function errorHandler($severity, $message, $file, $line)
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorTypes = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Notice',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        $errorType = isset($errorTypes[$severity]) ? $errorTypes[$severity] : 'Unknown Error';
        $errorMessage = "[{$errorType}] {$message} in {$file} on line {$line}";

        // Log the error
        static::log($errorMessage, [
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
        ]);

        // Display error if configured
        if (\AppConfig::debug('EXCEPTION_ERROR_TO_SHOW')) {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 5px; border-radius: 4px;'>";
            echo "<strong>{$errorType}:</strong> {$message}<br>";
            echo "<strong>File:</strong> {$file}<br>";
            echo "<strong>Line:</strong> {$line}<br>";
            if (\AppConfig::debug('DISPLAY_DEBUG_BACKTRACE')) {
                echo "<strong>Backtrace:</strong><br><pre>" . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5), true) . "</pre>";
            }
            echo "</div>";
        }

        return true;
    }

    /**
     * Custom exception handler
     * @param \Throwable $exception
     * @return void
     */
    public static function exceptionHandler($exception)
    {
        $errorMessage = "[Exception] " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();

        // Log the exception
        static::log($errorMessage, [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Display exception if configured
        if (\AppConfig::debug('EXCEPTION_ERROR_TO_SHOW')) {
            echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 5px; border-radius: 4px;'>";
            echo "<strong>Exception:</strong> " . get_class($exception) . "<br>";
            echo "<strong>Message:</strong> " . $exception->getMessage() . "<br>";
            echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
            echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
            if (\AppConfig::debug('DISPLAY_DEBUG_BACKTRACE')) {
                echo "<strong>Stack Trace:</strong><br><pre>" . $exception->getTraceAsString() . "</pre>";
            }
            echo "</div>";
        }
    }

    /**
     * Shutdown handler for fatal errors
     * @return void
     */
    public static function shutdownHandler()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            static::errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    /**
     * Dump variables with beautiful output
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    public static function dump(...$vars)
    {
        static::init();
        
        foreach ($vars as $var) {
            VarDumper::dump($var);
        }
    }

    /**
     * Dump variables and stop execution
     * @param mixed ...$vars Variables to dump
     * @return void
     */
    public static function dd(...$vars)
    {
        static::dump(...$vars);
        exit(1);
    }

    /**
     * Log debug information
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log($message, array $context = [])
    {
        static::init();
        
        // Determine log file based on context
        $logFile = 'logs/debug.log';
        if (isset($context['severity']) && $context['severity'] >= E_ERROR) {
            $logFile = 'logs/errors.log';
        }
        
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_PRETTY_PRINT) : '';
        $logEntry = "[{$timestamp}] DEBUG: {$message}{$contextStr}" . PHP_EOL;
        
        // Write to file if logging is enabled
        if (\AppConfig::debug('LOG_TO_FILE')) {
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
        
        // // Output to console if enabled
        // if (\AppConfig::debug('LOG_TO_CONSOLE')) {
        //     echo $logEntry;
        // }
    }

    /**
     * Check if debugging is enabled
     * @return bool
     */
    public static function isDebugEnabled()
    {
        return static::$initialized;
    }

    /**
     * Get debug information about the current environment
     * @return array
     */
    public static function getDebugInfo()
    {
        static::init();
        
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage(true);
        
        return [
            'php_version' => PHP_VERSION,
            'memory_usage' => $currentMemory,
            'memory_peak' => memory_get_peak_usage(true),
            'memory_start' => static::$memoryStart,
            'memory_delta' => $currentMemory - static::$memoryStart,
            'execution_time' => $currentTime - static::$startTime,
            'included_files' => count(get_included_files()),
            'debug_config' => [
                'log_to_file' => \AppConfig::debug('LOG_TO_FILE'),
                'log_to_console' => \AppConfig::debug('LOG_TO_CONSOLE'),
                'display_debug_console' => \AppConfig::debug('DISPLAY_DEBUG_CONSOLE'),
                'exception_error_handler' => \AppConfig::debug('EXCEPTION_ERROR_HANDLER'),
            ]
        ];
    }

    /**
     * Add debug information to the debug info array
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function addDebugInfo($key, $value)
    {
        static::$debugInfo[$key] = $value;
    }

    /**
     * Get all debug information
     * @return array
     */
    public static function getAllDebugInfo()
    {
        return array_merge(static::getDebugInfo(), static::$debugInfo);
    }

    /**
     * Legacy compatibility method
     * @return \App\Debugger
     */
    public static function initConsole()
    {
        static::init();
        return new static();
    }

    /**
     * Legacy compatibility method
     * @return \App\Debugger|null
     */
    public static function getDebugBar()
    {
        static::init();
        return static::$initialized ? new static() : null;
    }

    /**
     * Legacy compatibility method
     * @return bool
     */
    public static function isDebugBar()
    {
        return static::isDebugEnabled();
    }

    /**
     * Get JavaScript renderer
     * @return object
     */
    public static function getJavascriptRenderer()
    {
        static::init();
        
        // Return a simple object with renderHead method
        return new JavascriptRenderer();
    }

    /**
     * Get formatted backtrace string
     * @param int $limit Maximum number of stack frames to return
     * @return string
     */
    public static function getBacktrace($limit = 10)
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);
        $formatted = '';
        
        foreach ($backtrace as $index => $frame) {
            $formatted .= "#{$index} ";
            
            if (isset($frame['file'])) {
                $formatted .= $frame['file'];
                if (isset($frame['line'])) {
                    $formatted .= "({$frame['line']})";
                }
            }
            
            if (isset($frame['class'])) {
                $formatted .= ": {$frame['class']}::{$frame['function']}()";
            } elseif (isset($frame['function'])) {
                $formatted .= ": {$frame['function']}()";
            }
            
            $formatted .= PHP_EOL;
        }
        
        return $formatted;
    }

    /**
     * Display debug console if enabled
     * @return string
     */
    public static function renderDebugConsole()
    {
        if (!\AppConfig::debug('DISPLAY_DEBUG_CONSOLE')) {
            return '';
        }

        $debugInfo = static::getAllDebugInfo();
        
        $html = '<div id="debug-console" style="position: fixed; bottom: 0; left: 0; width: 100%; background: #333; color: #fff; padding: 10px; font-family: monospace; font-size: 12px; z-index: 9999; max-height: 300px; overflow-y: auto;">';
        $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
        $html .= '<h3 style="margin: 0;">Debug Console</h3>';
        $html .= '<button onclick="document.getElementById(\'debug-console\').style.display=\'none\'" style="background: #f44336; color: white; border: none; padding: 5px 10px; cursor: pointer;">Close</button>';
        $html .= '</div>';
        
        foreach ($debugInfo as $key => $value) {
            $html .= '<div style="margin-bottom: 5px;">';
            $html .= '<strong>' . htmlspecialchars($key) . ':</strong> ';
            if (is_array($value)) {
                $html .= '<pre style="margin: 0; padding: 5px; background: #222; border-radius: 3px;">' . htmlspecialchars(print_r($value, true)) . '</pre>';
            } else {
                $html .= htmlspecialchars($value);
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}

/**
 * JavaScript renderer class for debug console
 */
class JavascriptRenderer
{
    /**
     * Render head content
     * @return string
     */
    public function renderHead()
    {
        return '';
    }
}
