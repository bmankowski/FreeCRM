<?php
/**
 * Scan TPL files for MVC violations
 * Usage: php scripts/analyze_tpl_violations.php [directory]
 *        php scripts/analyze_tpl_violations.php [file.tpl]
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

// Include App bootstrap if available
$rootDir = dirname(__DIR__);
if (file_exists($rootDir . '/include.inc')) {
    require_once $rootDir . '/include.inc';
    require_once 'include/main/WebUI.php';
}

class TplMvcAnalyzer {
    private $violations = [];
    
    // Patterns to detect
    private $patterns = [
        'model_call' => [
            'pattern' => '/[A-Z][a-zA-Z_]*_Model::[a-zA-Z]+\(/i',
            'description' => 'Direct model static method call',
            'severity' => 'high',
        ],
        'model_instance' => [
            'pattern' => '/[A-Z][a-zA-Z_]*_Model::getInstance\(/i',
            'description' => 'Model instantiation in template',
            'severity' => 'high',
        ],
        'appconfig' => [
            'pattern' => '/AppConfig::[a-zA-Z]+\(/i',
            'description' => 'Direct AppConfig call',
            'severity' => 'medium',
        ],
        'privilege' => [
            'pattern' => '/\\\\App\\\\Privilege::/i',
            'description' => 'Permission check in template',
            'severity' => 'high',
        ],
        'json_encode' => [
            'pattern' => '/\\\\App\\\\Json::encode\(/i',
            'description' => 'JSON encoding in template',
            'severity' => 'medium',
        ],
        'debugger' => [
            'pattern' => '/\\\\App\\\\Debugger::/i',
            'description' => 'Debugger call in template',
            'severity' => 'low',
        ],
        'util_helper' => [
            'pattern' => '/Vtiger_Util_Helper::/i',
            'description' => 'Utility helper call',
            'severity' => 'medium',
        ],
        'array_operations' => [
            'pattern' => '/(array_push|array_key_exists|count|in_array)\(/i',
            'description' => 'Business logic array operation',
            'severity' => 'medium',
        ],
        'vtlib_functions' => [
            'pattern' => '/vtlib\\\\Functions::/i',
            'description' => 'Direct vtlib Functions call',
            'severity' => 'medium',
        ],
        'field_classes' => [
            'pattern' => '/\\\\App\\\\Fields::/i',
            'description' => 'Direct field utility call',
            'severity' => 'medium',
        ],
        'uitype_calls' => [
            'pattern' => '/[A-Z][a-zA-Z_]*_UIType::/i',
            'description' => 'Direct UIType method call',
            'severity' => 'medium',
        ],
        'complex_assign' => [
            'pattern' => '/\{assign[^}]*value=[^}]*(::|\(count\(|\(array)/i',
            'description' => 'Complex assignment with logic',
            'severity' => 'medium',
        ],
    ];
    
    public function analyzeTplFile($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        
        foreach ($lines as $lineNum => $line) {
            // Skip Smarty comments
            if (preg_match('/\{\*.*\*\}/', $line)) {
                continue;
            }
            
            foreach ($this->patterns as $type => $config) {
                if (preg_match($config['pattern'], $line, $matches)) {
                    $this->violations[] = [
                        'file' => $filePath,
                        'line' => $lineNum + 1,
                        'type' => $type,
                        'description' => $config['description'],
                        'severity' => $config['severity'],
                        'code' => trim($line),
                        'match' => $matches[0] ?? '',
                    ];
                }
            }
        }
    }
    
    public function analyzeDirectory($dir) {
        if (!is_dir($dir)) {
            throw new Exception("Directory not found: $dir");
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        $fileCount = 0;
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'tpl') {
                try {
                    $this->analyzeTplFile($file->getPathname());
                    $fileCount++;
                } catch (Exception $e) {
                    echo "Warning: Could not analyze {$file->getPathname()}: {$e->getMessage()}\n";
                }
            }
        }
        
        return $fileCount;
    }
    
    public function generateReport($format = 'text') {
        if ($format === 'json') {
            return json_encode([
                'total_violations' => count($this->violations),
                'by_type' => $this->getViolationsByType(),
                'by_file' => $this->getViolationsByFile(),
                'by_severity' => $this->getViolationsBySeverity(),
                'violations' => $this->violations,
            ], JSON_PRETTY_PRINT);
        }
        
        $report = "\n" . str_repeat('=', 80) . "\n";
        $report .= "MVC Violations Analysis Report\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= str_repeat('=', 80) . "\n\n";
        
        $report .= "SUMMARY\n";
        $report .= str_repeat('-', 80) . "\n";
        $report .= sprintf("Total Violations: %d\n", count($this->violations));
        $report .= sprintf("Files Affected: %d\n", count($this->getViolationsByFile()));
        $report .= "\n";
        
        // By severity
        $bySeverity = $this->getViolationsBySeverity();
        $report .= "By Severity:\n";
        foreach (['high', 'medium', 'low'] as $severity) {
            $count = count($bySeverity[$severity] ?? []);
            $report .= sprintf("  %-10s: %d\n", strtoupper($severity), $count);
        }
        $report .= "\n";
        
        // By type
        $byType = $this->getViolationsByType();
        $report .= "By Type:\n";
        arsort($byType);
        foreach ($byType as $type => $count) {
            $report .= sprintf("  %-25s: %d\n", $type, $count);
        }
        $report .= "\n";
        
        // Detailed violations by type
        $report .= str_repeat('=', 80) . "\n";
        $report .= "DETAILED VIOLATIONS\n";
        $report .= str_repeat('=', 80) . "\n\n";
        
        $groupedByType = [];
        foreach ($this->violations as $violation) {
            $groupedByType[$violation['type']][] = $violation;
        }
        
        foreach ($groupedByType as $type => $violations) {
            $config = $this->patterns[$type];
            $report .= sprintf("TYPE: %s [%s]\n", strtoupper($type), strtoupper($config['severity']));
            $report .= sprintf("Description: %s\n", $config['description']);
            $report .= sprintf("Count: %d\n", count($violations));
            $report .= str_repeat('-', 80) . "\n";
            
            // Show up to 10 examples
            foreach (array_slice($violations, 0, 10) as $violation) {
                $relativePath = str_replace(dirname(__DIR__) . '/', '', $violation['file']);
                $report .= sprintf(
                    "\n  %s:%d\n  > %s\n",
                    $relativePath,
                    $violation['line'],
                    trim(substr($violation['code'], 0, 100))
                );
            }
            
            if (count($violations) > 10) {
                $report .= sprintf("\n  ... and %d more\n", count($violations) - 10);
            }
            
            $report .= "\n" . str_repeat('-', 80) . "\n\n";
        }
        
        return $report;
    }
    
    public function getViolationsByType() {
        $byType = [];
        foreach ($this->violations as $violation) {
            if (!isset($byType[$violation['type']])) {
                $byType[$violation['type']] = 0;
            }
            $byType[$violation['type']]++;
        }
        return $byType;
    }
    
    public function getViolationsByFile() {
        $byFile = [];
        foreach ($this->violations as $violation) {
            $file = str_replace(dirname(__DIR__) . '/', '', $violation['file']);
            if (!isset($byFile[$file])) {
                $byFile[$file] = [];
            }
            $byFile[$file][] = $violation;
        }
        return $byFile;
    }
    
    public function getViolationsBySeverity() {
        $bySeverity = ['high' => [], 'medium' => [], 'low' => []];
        foreach ($this->violations as $violation) {
            $bySeverity[$violation['severity']][] = $violation;
        }
        return $bySeverity;
    }
    
    public function getViolations() {
        return $this->violations;
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $target = $argv[1] ?? null;
    $format = $argv[2] ?? 'text';
    
    if (!$target) {
        echo "Usage: php analyze_tpl_violations.php <directory|file.tpl> [format]\n";
        echo "  format: text (default) or json\n";
        echo "\nExamples:\n";
        echo "  php analyze_tpl_violations.php layouts/basic/modules\n";
        echo "  php analyze_tpl_violations.php layouts/basic/modules/Vtiger/Header.tpl\n";
        echo "  php analyze_tpl_violations.php layouts/basic/modules json\n";
        exit(1);
    }
    
    $analyzer = new TplMvcAnalyzer();
    
    try {
        if (is_dir($target)) {
            echo "Analyzing directory: $target\n";
            $fileCount = $analyzer->analyzeDirectory($target);
            echo "Analyzed $fileCount TPL files\n";
        } elseif (is_file($target)) {
            echo "Analyzing file: $target\n";
            $analyzer->analyzeTplFile($target);
        } else {
            echo "Error: '$target' is not a valid file or directory\n";
            exit(1);
        }
        
        $report = $analyzer->generateReport($format);
        echo $report;
        
        // Save report to file if text format
        if ($format === 'text') {
            $reportFile = dirname(__DIR__) . '/cache/tpl_violations_report.txt';
            @mkdir(dirname($reportFile), 0755, true);
            file_put_contents($reportFile, $report);
            echo "\nReport saved to: $reportFile\n";
        }
        
        // Save JSON data
        $jsonFile = dirname(__DIR__) . '/cache/tpl_violations.json';
        @mkdir(dirname($jsonFile), 0755, true);
        file_put_contents($jsonFile, $analyzer->generateReport('json'));
        echo "JSON data saved to: $jsonFile\n";
        
        // Exit code based on violations
        $violations = $analyzer->getViolations();
        exit(empty($violations) ? 0 : 1);
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

