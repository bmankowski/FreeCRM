<?php
/**
 * Debug Console Helper for FreeYeti Application
 * 
 * This file provides helper functions to integrate debugging into the web interface.
 * Include this file in your templates or main application files to enable debug console.
 */

namespace App;

class DebugConsole
{
    /**
     * Render debug console HTML
     * @return string
     */
    public static function render()
    {
        if (!\AppConfig::debug('DISPLAY_DEBUG_CONSOLE')) {
            return '';
        }

        $debugInfo = Debugger::getAllDebugInfo();
        
        $html = '<div id="debug-console" style="position: fixed; bottom: 0; left: 0; width: 100%; background: #1e1e1e; color: #d4d4d4; padding: 15px; font-family: \'Consolas\', \'Monaco\', \'Courier New\', monospace; font-size: 12px; z-index: 9999; max-height: 400px; overflow-y: auto; border-top: 2px solid #007acc; box-shadow: 0 -2px 10px rgba(0,0,0,0.3);">';
        
        // Header
        $html .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #333; padding-bottom: 10px;">';
        $html .= '<h3 style="margin: 0; color: #007acc; font-size: 16px;">🐛 Debug Console</h3>';
        $html .= '<div>';
        $html .= '<button onclick="toggleDebugSection(\'info\')" style="background: #007acc; color: white; border: none; padding: 5px 10px; margin-right: 5px; cursor: pointer; border-radius: 3px;">Info</button>';
        $html .= '<button onclick="toggleDebugSection(\'logs\')" style="background: #28a745; color: white; border: none; padding: 5px 10px; margin-right: 5px; cursor: pointer; border-radius: 3px;">Logs</button>';
        $html .= '<button onclick="toggleDebugSection(\'performance\')" style="background: #ffc107; color: black; border: none; padding: 5px 10px; margin-right: 5px; cursor: pointer; border-radius: 3px;">Performance</button>';
        $html .= '<button onclick="document.getElementById(\'debug-console\').style.display=\'none\'" style="background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Close</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Debug Info Section
        $html .= '<div id="debug-info" class="debug-section">';
        $html .= '<h4 style="color: #007acc; margin-bottom: 10px;">System Information</h4>';
        foreach ($debugInfo as $key => $value) {
            $html .= '<div style="margin-bottom: 8px; padding: 5px; background: #2d2d2d; border-radius: 3px;">';
            $html .= '<strong style="color: #4ec9b0;">' . htmlspecialchars($key) . ':</strong> ';
            if (is_array($value)) {
                $html .= '<pre style="margin: 0; padding: 5px; background: #1e1e1e; border-radius: 3px; font-size: 11px;">' . htmlspecialchars(print_r($value, true)) . '</pre>';
            } else {
                $html .= '<span style="color: #ce9178;">' . htmlspecialchars($value) . '</span>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        
        // Logs Section
        $html .= '<div id="debug-logs" class="debug-section" style="display: none;">';
        $html .= '<h4 style="color: #28a745; margin-bottom: 10px;">Recent Logs</h4>';
        $lastLines = Log::getLastLines(20);
        if (!empty($lastLines)) {
            foreach ($lastLines as $line) {
                $html .= '<div style="margin-bottom: 3px; padding: 3px; background: #2d2d2d; border-radius: 3px; font-size: 11px;">';
                $html .= '<span style="color: #ce9178;">' . htmlspecialchars($line) . '</span>';
                $html .= '</div>';
            }
        } else {
            $html .= '<div style="color: #6a9955;">No recent logs found.</div>';
        }
        $html .= '</div>';
        
        // Performance Section
        $html .= '<div id="debug-performance" class="debug-section" style="display: none;">';
        $html .= '<h4 style="color: #ffc107; margin-bottom: 10px;">Performance Metrics</h4>';
        
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        
        $html .= '<div style="margin-bottom: 8px; padding: 5px; background: #2d2d2d; border-radius: 3px;">';
        $html .= '<strong style="color: #4ec9b0;">Memory Usage:</strong> <span style="color: #ce9178;">' . self::formatBytes($memoryUsage) . '</span><br>';
        $html .= '<strong style="color: #4ec9b0;">Peak Memory:</strong> <span style="color: #ce9178;">' . self::formatBytes($memoryPeak) . '</span><br>';
        $html .= '<strong style="color: #4ec9b0;">Execution Time:</strong> <span style="color: #ce9178;">' . round($executionTime, 4) . 's</span><br>';
        $html .= '<strong style="color: #4ec9b0;">Included Files:</strong> <span style="color: #ce9178;">' . count(get_included_files()) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // JavaScript for toggling sections
        $html .= '<script>
        function toggleDebugSection(section) {
            // Hide all sections
            var sections = document.querySelectorAll(".debug-section");
            for (var i = 0; i < sections.length; i++) {
                sections[i].style.display = "none";
            }
            
            // Show selected section
            var targetSection = document.getElementById("debug-" + section);
            if (targetSection) {
                targetSection.style.display = "block";
            }
        }
        
        // Auto-refresh logs every 5 seconds
        setInterval(function() {
            if (document.getElementById("debug-logs").style.display !== "none") {
                // You could implement AJAX refresh here if needed
            }
        }, 5000);
        </script>';
        
        return $html;
    }
    
    /**
     * Format bytes to human readable format
     * @param int $bytes
     * @return string
     */
    private static function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Add debug information to the console
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function addInfo($key, $value)
    {
        Debugger::addDebugInfo($key, $value);
    }
    
    /**
     * Log a message to the debug console
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function log($message, array $context = [])
    {
        Log::debug($message, $context);
    }
    
    /**
     * Dump variables in the debug console
     * @param mixed ...$vars
     * @return void
     */
    public static function dump(...$vars)
    {
        Debugger::dump(...$vars);
    }
    
    /**
     * Dump variables and stop execution
     * @param mixed ...$vars
     * @return void
     */
    public static function dd(...$vars)
    {
        Debugger::dd(...$vars);
    }
}
