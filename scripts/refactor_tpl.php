<?php
/**
 * Automated TPL Refactoring
 * Usage: php scripts/refactor_tpl.php <file_path> [--dry-run]
 */

if (php_sapi_name() !== 'cli') {
    die('This script must be run from command line');
}

class TplRefactor {
    private $replacements = [];
    private $dryRun = false;
    
    public function __construct($dryRun = false) {
        $this->dryRun = $dryRun;
        $this->setupReplacements();
    }
    
    private function setupReplacements() {
        // Simple replacements that can be done automatically
        // Format: [pattern => replacement, requiresControllerCode => bool]
        $this->replacements = [
            // AppConfig::search calls
            [
                'pattern' => '/AppConfig::search\([\'"]GLOBAL_SEARCH_AUTOCOMPLETE[\'"]\)/',
                'replacement' => '$CONFIG.gsAutocomplete',
                'controller' => "\$config['gsAutocomplete'] = AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE');",
            ],
            [
                'pattern' => '/AppConfig::search\([\'"]GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH[\'"]\)/',
                'replacement' => '$CONFIG.gsMinLength',
                'controller' => "\$config['gsMinLength'] = AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_MIN_LENGTH');",
            ],
            [
                'pattern' => '/AppConfig::search\([\'"]GLOBAL_SEARCH_AUTOCOMPLETE_LIMIT[\'"]\)/',
                'replacement' => '$CONFIG.gsAmountResponse',
                'controller' => "\$config['gsAmountResponse'] = AppConfig::search('GLOBAL_SEARCH_AUTOCOMPLETE_LIMIT');",
            ],
            [
                'pattern' => '/AppConfig::performance\([\'"]AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE[\'"]\)/',
                'replacement' => '$CONFIG.autoRefreshListOnChange',
                'controller' => "\$config['autoRefreshListOnChange'] = AppConfig::performance('AUTO_REFRESH_RECORD_LIST_ON_SELECT_CHANGE');",
            ],
            [
                'pattern' => '/AppConfig::performance\([\'"]INTERVAL_FOR_NOTIFICATION_NUMBER_CHECK[\'"]\)/',
                'replacement' => '$CONFIG.intervalForNotificationNumberCheck',
                'controller' => "\$config['intervalForNotificationNumberCheck'] = AppConfig::performance('INTERVAL_FOR_NOTIFICATION_NUMBER_CHECK');",
            ],
            [
                'pattern' => '/AppConfig::security\([\'"]FIELDS_REFERENCES_DEPENDENT[\'"]\)/',
                'replacement' => '$CONFIG.fieldsReferencesDependent',
                'controller' => "\$config['fieldsReferencesDependent'] = AppConfig::security('FIELDS_REFERENCES_DEPENDENT');",
            ],
            [
                'pattern' => '/AppConfig::sounds\(\)/',
                'replacement' => '$CONFIG.sounds',
                'controller' => "\$config['sounds'] = AppConfig::sounds();",
            ],
            
            // Debugger calls
            [
                'pattern' => '/\\\App\\\\Debugger::isDebugBar\(\)/',
                'replacement' => '$DEBUG_BAR_ENABLED',
                'controller' => "\$viewer->assign('DEBUG_BAR_ENABLED', \App\\Debugger::isDebugBar());",
            ],
        ];
    }
    
    public function refactorFile($filePath) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: $filePath");
        }
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $appliedReplacements = [];
        
        // Apply replacements
        foreach ($this->replacements as $replacement) {
            $newContent = preg_replace(
                $replacement['pattern'], 
                $replacement['replacement'], 
                $content,
                -1,
                $count
            );
            
            if ($count > 0) {
                $content = $newContent;
                $appliedReplacements[] = [
                    'pattern' => $replacement['pattern'],
                    'replacement' => $replacement['replacement'],
                    'controller' => $replacement['controller'],
                    'count' => $count,
                ];
            }
        }
        
        // Check if changes were made
        if ($content === $originalContent) {
            return [
                'changed' => false,
                'replacements' => [],
            ];
        }
        
        if (!$this->dryRun) {
            // Create backup
            $backupPath = $filePath . '.backup_' . date('YmdHis');
            copy($filePath, $backupPath);
            
            // Write refactored content
            file_put_contents($filePath, $content);
        }
        
        return [
            'changed' => true,
            'replacements' => $appliedReplacements,
            'backup' => $this->dryRun ? null : $backupPath,
        ];
    }
    
    public function generateControllerCode($tplFile) {
        // Analyze what the template needs and generate controller code
        if (!file_exists($tplFile)) {
            return "// File not found: $tplFile\n";
        }
        
        $content = file_get_contents($tplFile);
        $code = "/**\n";
        $code .= " * Controller code for: " . basename($tplFile) . "\n";
        $code .= " * Add this to the appropriate controller's process() method\n";
        $code .= " */\n\n";
        
        $hasCode = false;
        
        // Detect AppConfig calls
        $configCalls = [];
        if (preg_match_all('/AppConfig::([a-z]+)\([\'"]([A-Z_]+)[\'"]\)/i', $content, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $method = $matches[1][$i];
                $param = $matches[2][$i];
                $key = $this->camelCase($param);
                if (!isset($configCalls[$key])) {
                    $configCalls[$key] = "AppConfig::$method('$param')";
                }
            }
        }
        
        if (!empty($configCalls)) {
            $hasCode = true;
            $code .= "// Configuration values\n";
            $code .= "\$config = [\n";
            foreach ($configCalls as $key => $call) {
                $code .= "    '$key' => $call,\n";
            }
            $code .= "];\n";
            $code .= "\$viewer->assign('CONFIG', \$config);\n\n";
        }
        
        // Detect Model::getInstance calls
        $modelInstances = [];
        if (preg_match_all('/([A-Z][a-zA-Z_]*)_Model::getInstance\(([^)]+)\)/i', $content, $matches)) {
            foreach ($matches[0] as $i => $match) {
                $modelClass = $matches[1][$i];
                $params = $matches[2][$i];
                $varName = strtoupper(str_replace('_Model', '', $modelClass)) . '_MODEL';
                if (!isset($modelInstances[$varName])) {
                    $modelInstances[$varName] = [
                        'class' => $modelClass,
                        'params' => $params,
                    ];
                }
            }
        }
        
        if (!empty($modelInstances)) {
            $hasCode = true;
            $code .= "// Model instances\n";
            foreach ($modelInstances as $varName => $info) {
                $code .= "\${$varName} = {$info['class']}_Model::getInstance({$info['params']});\n";
                $code .= "\$viewer->assign('$varName', \${$varName});\n";
            }
            $code .= "\n";
        }
        
        // Detect permission checks
        if (preg_match_all('/\\\App\\\\Privilege::isPermitted\(([^)]+)\)/i', $content, $matches)) {
            $hasCode = true;
            $code .= "// Permission checks\n";
            foreach ($matches[0] as $i => $match) {
                $params = $matches[1][$i];
                $varName = 'isPermitted' . ($i + 1);
                $code .= "\${$varName} = \App\\Privilege::isPermitted($params);\n";
                $code .= "\$viewer->assign('$varName', \${$varName});\n";
            }
            $code .= "\n";
        }
        
        // Detect JSON encodings
        if (preg_match_all('/\\\App\\\\Json::encode\(\$([A-Z_]+)\)/i', $content, $matches)) {
            $hasCode = true;
            $code .= "// JSON encoded data\n";
            foreach (array_unique($matches[1]) as $varName) {
                $code .= "// Note: Encode \${$varName} before assigning\n";
                $code .= "\$viewer->assign('{$varName}_JSON', \App\\Json::encode(\${$varName}));\n";
            }
            $code .= "\n";
        }
        
        // Detect Vtiger_Util_Helper calls
        if (preg_match('/Vtiger_Util_Helper::toSafeHTML/', $content)) {
            $hasCode = true;
            $code .= "// Note: Apply Vtiger_Util_Helper::toSafeHTML() to strings before assigning\n\n";
        }
        
        if (!$hasCode) {
            return "// No controller changes detected for this template\n";
        }
        
        return $code;
    }
    
    private function camelCase($string) {
        return lcfirst(str_replace('_', '', ucwords(strtolower($string), '_')));
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $filePath = null;
    $dryRun = false;
    
    // Parse arguments
    for ($i = 1; $i < $argc; $i++) {
        if ($argv[$i] === '--dry-run') {
            $dryRun = true;
        } else {
            $filePath = $argv[$i];
        }
    }
    
    if (!$filePath) {
        echo "Usage: php refactor_tpl.php <file_path> [--dry-run]\n";
        echo "\nOptions:\n";
        echo "  --dry-run    Show changes without modifying files\n";
        echo "\nExamples:\n";
        echo "  php refactor_tpl.php layouts/basic/modules/Vtiger/Header.tpl\n";
        echo "  php refactor_tpl.php layouts/basic/modules/Vtiger/Header.tpl --dry-run\n";
        exit(1);
    }
    
    $refactor = new TplRefactor($dryRun);
    
    try {
        echo "\n";
        echo str_repeat('=', 80) . "\n";
        echo "TPL Refactoring Tool\n";
        echo str_repeat('=', 80) . "\n";
        echo "File: $filePath\n";
        echo "Mode: " . ($dryRun ? "DRY RUN (no changes will be made)" : "LIVE") . "\n";
        echo str_repeat('-', 80) . "\n\n";
        
        $result = $refactor->refactorFile($filePath);
        
        if ($result['changed']) {
            echo "✓ Changes detected!\n\n";
            
            echo "Replacements made:\n";
            foreach ($result['replacements'] as $i => $replacement) {
                echo sprintf(
                    "  %d. Pattern: %s\n     Replacement: %s\n     Occurrences: %d\n\n",
                    $i + 1,
                    $replacement['pattern'],
                    $replacement['replacement'],
                    $replacement['count']
                );
            }
            
            if (!$dryRun) {
                echo "✓ File updated: $filePath\n";
                echo "✓ Backup created: {$result['backup']}\n\n";
            } else {
                echo "ℹ DRY RUN - No files were modified\n\n";
            }
            
            echo str_repeat('=', 80) . "\n";
            echo "REQUIRED CONTROLLER CODE\n";
            echo str_repeat('=', 80) . "\n";
            echo $refactor->generateControllerCode($filePath);
            echo str_repeat('=', 80) . "\n";
            
        } else {
            echo "ℹ No automatic refactorings available for this file\n\n";
            echo "However, you may still need to make manual changes.\n";
            echo "Generated controller code suggestions:\n\n";
            echo str_repeat('=', 80) . "\n";
            echo $refactor->generateControllerCode($filePath);
            echo str_repeat('=', 80) . "\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

