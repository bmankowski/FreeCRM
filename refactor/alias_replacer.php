<?php
/**
 * Alias Replacer Tool
 * 
 * This tool reads aliases from GlobalAliases.php and replaces all occurrences
 * of the alias keys with their corresponding values throughout the codebase.
 * 
 * Usage:
 * php alias_replacer.php [--dry-run] [--path=/path/to/search]
 * 
 * Options:
 * --dry-run    Show what would be changed without making actual changes
 * --path       Specify the path to search (default: current directory)
 * --help       Show this help message
 */

class AliasReplacer
{
    private $aliases = [];
    private $dryRun = false;
    private $searchPath = '.';
    private $changes = [];
    private $fileExtensions = ['php', 'tpl'];
    private $excludeDirs = ['vendor', 'node_modules', '.git', 'cache', 'storage', 'logs', 'old_modules', '.backup'];

    public function __construct($args = [])
    {
        $this->parseArguments($args);
        $this->loadAliases();
    }

    private function parseArguments($args)
    {
        foreach ($args as $arg) {
            if ($arg === '--dry-run') {
                $this->dryRun = true;
            } elseif (strpos($arg, '--path=') === 0) {
                $this->searchPath = substr($arg, 7);
            } elseif ($arg === '--help') {
                $this->showHelp();
                exit(0);
            }
        }
    }

    private function showHelp()
    {
        echo "Alias Replacer Tool\n\n";
        echo "Usage: php alias_replacer.php [options]\n\n";
        echo "Options:\n";
        echo "  --dry-run    Show what would be changed without making actual changes\n";
        echo "  --path=DIR   Specify the path to search (default: current directory)\n";
        echo "  --help       Show this help message\n\n";
        echo "Examples:\n";
        echo "  php alias_replacer.php --dry-run\n";
        echo "  php alias_replacer.php --path=/path/to/project\n";
    }

    private function loadAliases()
    {
        // Alias map from migrate-alias-simple.php
        $this->aliases = [
            // Batch 1 (completed)
            'Vtiger_Link_Model' => 'App\Modules\Vtiger\Models\Link',
            'Vtiger_ListView_Model' => 'App\Modules\Vtiger\Models\ListView',
            'Users_Module_Model' => 'App\Modules\Users\Models\Module',
            'Vtiger_Action_Model' => 'App\Modules\Vtiger\Models\Action',
            'Vtiger_Block_Model' => 'App\Modules\Vtiger\Models\Block',
            'Vtiger_Relation_Model' => 'App\Modules\Vtiger\Models\Relation',
            // Batch 2 (recommended)
            'Settings_Vtiger_Module_Model' => 'App\Modules\Settings\Vtiger\Models\Module',
            'VTWorkflowManager' => 'App\Modules\com_vtiger_workflow\VTWorkflowManager',
            'Settings_Vtiger_Record_Model' => 'App\Modules\Settings\Vtiger\Models\Record',
            'Vtiger_Paging_Model' => 'App\Modules\Vtiger\Models\Paging',
            'Vtiger_DetailView_Model' => 'App\Modules\Vtiger\Models\DetailView',
            'Vtiger_DependencyPicklist' => 'App\Modules\PickList\DependencyPicklist',
            'VTJsonCondition' => 'App\Modules\com_vtiger_workflow\VTJsonCondition',
            'Vtiger_TreeCategoryModal_Model' => 'App\Modules\Vtiger\Models\TreeCategoryModal',
            'Vtiger_Utility_Model' => 'App\Modules\Vtiger\Models\Utility',
            'Vtiger_TreeView_Model' => 'App\Modules\Vtiger\Models\TreeView',
            'Vtiger_DashBoard_Model' => 'App\Modules\Vtiger\Models\DashBoard',
            // Batch 3
            'Vtiger_JsScript_Model' => 'App\Runtime\Vtiger_JsScript_Model',
            'Vtiger_CRMEntity' => 'App\CRMEntity',
            'Vtiger_CssScript_Model' => 'App\Runtime\Vtiger_CssScript_Model',
            'ModTracker_ModTrackerHandler_Handler' => 'App\Modules\ModTracker\Handlers\Handler',
            'Vtiger_Base_UIType' => 'App\Modules\Vtiger\UiTypes\Base',
            'Vtiger_Workflow_Handler' => 'App\Modules\com_vtiger_workflow\VTWorkflowEventHandler',
            'Vtiger_ModTracker_Model' => 'App\Modules\ModTracker\Models\ModTracker',
            // Batch 4 - Settings modules
            'Settings_Groups_Record_Model' => 'App\Modules\Settings\Groups\Models\Record',
            'Settings_Currency_Record_Model' => 'App\Modules\Settings\Currency\Models\Record',
            'Settings_CronTasks_Record_Model' => 'App\Modules\Settings\CronTasks\Models\Record',
            'Settings_PDF_Record_Model' => 'App\Modules\Settings\PDF\Models\Record',
            'Settings_AdvancedPermission_Record_Model' => 'App\Modules\Settings\AdvancedPermission\Models\Record',
            'Settings_BruteForce_Module_Model' => 'App\Modules\Settings\BruteForce\Models\Module',
            'Settings_MailSmtp_Record_Model' => 'App\Modules\Settings\MailSmtp\Models\Record',
            'Settings_Companies_Record_Model' => 'App\Modules\Settings\Companies\Models\Record',
            'Settings_Currency_Module_Model' => 'App\Modules\Settings\Currency\Models\Module',
            'Settings_CronTasks_Module_Model' => 'App\Modules\Settings\CronTasks\Models\Module',
            'Settings_Mail_Record_Model' => 'App\Modules\Settings\Mail\Models\Record',
            'Settings_Mail_Module_Model' => 'App\Modules\Settings\Mail\Models\Module',
            'Settings_Groups_Module_Model' => 'App\Modules\Settings\Groups\Models\Module',
            // Batch 5 - More Settings
            'Settings_Workflows_Record_Model' => 'App\Modules\Settings\Workflows\Models\Record',
            'Settings_MappedFields_Module_Model' => 'App\Modules\Settings\MappedFields\Models\Module',
            'Settings_SharingAccess_Module_Model' => 'App\Modules\Settings\SharingAccess\Models\Module',
            'Settings_AutomaticAssignment_Record_Model' => 'App\Modules\Settings\AutomaticAssignment\Models\Record',
            'Settings_Picklist_Module_Model' => 'App\Modules\Settings\Picklist\Models\Module',
            'Settings_SharingAccess_Rule_Model' => 'App\Modules\Settings\SharingAccess\Models\Rule',
            'Settings_AutomaticAssignment_Module_Model' => 'App\Modules\Settings\AutomaticAssignment\Models\Module',
            'Settings_TreesManager_Record_Model' => 'App\Modules\Settings\TreesManager\Models\Record',
            'Settings_Leads_Mapping_Model' => 'App\Modules\Settings\Leads\Models\Mapping',
            'Settings_WebserviceUsers_Record_Model' => 'App\Modules\Settings\WebserviceUsers\Models\Record',
            'Settings_PickListDependency_Record_Model' => 'App\Modules\Settings\PickListDependency\Models\Record',
            'Settings_PickListDependency_Module_Model' => 'App\Modules\Settings\PickListDependency\Models\Module',
            'Settings_Leads_Module_Model' => 'App\Modules\Settings\Leads\Models\Module',
            'Settings_WebserviceUsers_Module_Model' => 'App\Modules\Settings\WebserviceUsers\Models\Module',
            'Settings_WebserviceApps_Record_Model' => 'App\Modules\Settings\WebserviceApps\Models\Record',
            // Batch 6 - More Settings
            'Settings_LangManagement_Module_Model' => 'App\Modules\Settings\LangManagement\Models\Module',
            'Settings_CurrencyUpdate_Module_Model' => 'App\Modules\Settings\CurrencyUpdate\Models\Module',
            'Settings_MappedFields_Field_Model' => 'App\Modules\Settings\MappedFields\Models\Field',
            'Settings_LayoutEditor_Field_Model' => 'App\Modules\Settings\LayoutEditor\Models\Field',
            'Settings_Menu_Record_Model' => 'App\Modules\Settings\Menu\Models\Record',
            'Settings_CustomView_Module_Model' => 'App\Modules\Settings\CustomView\Models\Module',
            'Settings_Inventory_Record_Model' => 'App\Modules\Settings\Inventory\Models\Record',
            'Settings_Menu_Module_Model' => 'App\Modules\Settings\Menu\Models\Module',
            'Settings_HideBlocks_Record_Model' => 'App\Modules\Settings\HideBlocks\Models\Record',
            'Settings_LayoutEditor_Block_Model' => 'App\Modules\Settings\LayoutEditor\Models\Block',
            'Settings_Leads_Field_Model' => 'App\Modules\Settings\Leads\Models\Field',
            'Settings_CurrencyUpdate_AbstractBank_Model' => 'App\Modules\Settings\CurrencyUpdate\Models\AbstractBank',
            'Settings_Github_Client_Model' => 'App\Modules\Settings\Github\Models\Client',
            'Settings_ApiAddress_Module_Model' => 'App\Modules\Settings\ApiAddress\Models\Module',
        ];

        if (empty($this->aliases)) {
            throw new Exception("No aliases found in alias map");
        }

        echo "Loaded " . count($this->aliases) . " aliases from migrate-alias-simple.php\n";
        echo "\n";
    }

    public function run()
    {
        echo "Searching for files in: " . realpath($this->searchPath) . "\n";
        echo "File extensions: " . implode(', ', $this->fileExtensions) . "\n";
        echo "Mode: " . ($this->dryRun ? "DRY RUN (no changes will be made)" : "LIVE (changes will be applied") . "\n\n";

        $files = $this->findFiles($this->searchPath);
        echo "Found " . count($files) . " files to process\n\n";

        foreach ($files as $file) {
            $this->processFile($file);
        }

        $this->showSummary();
    }

    private function findFiles($path)
    {
        $files = [];
        
        // If path is a single file, return it directly
        if (is_file($path)) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($extension, $this->fileExtensions)) {
                return [$path];
            }
            return [];
        }
        
        // If path is a directory, scan it recursively
        if (is_dir($path)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $extension = strtolower($file->getExtension());
                    $relativePath = str_replace($path . '/', '', $file->getPathname());
                    
                    // Check if file extension is in our list
                    if (in_array($extension, $this->fileExtensions)) {
                        // Check if path contains excluded directories
                        $shouldExclude = false;
                        foreach ($this->excludeDirs as $excludeDir) {
                            if (strpos($relativePath, $excludeDir . '/') === 0 || 
                                strpos($relativePath, '/' . $excludeDir . '/') !== false) {
                                $shouldExclude = true;
                                break;
                            }
                        }
                        
                        if (!$shouldExclude) {
                            $files[] = $file->getPathname();
                        }
                    }
                }
            }
        }

        return $files;
    }

    private function processFile($filePath)
    {
        // Skip alias definition files to avoid modifying the array definitions
        $basename = basename($filePath);
        if ($basename === 'GlobalAliases.php' || $basename === 'LegacyAliases.php') {
            return;
        }
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fileChanges = [];

        foreach ($this->aliases as $alias => $replacement) {
            $patterns = $this->getReplacementPatterns($alias, $replacement);
            
            foreach ($patterns as $pattern => $replacementPattern) {
                $matches = [];
                if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                    foreach ($matches[0] as $match) {
                        $fileChanges[] = [
                            'line' => $this->getLineNumber($content, $match[1]),
                            'original' => $match[0],
                            'replacement' => $replacementPattern,
                            'pattern' => $pattern
                        ];
                    }
                    
                    if (!$this->dryRun) {
                        $content = preg_replace($pattern, $replacementPattern, $content);
                    }
                }
            }
        }

        if (!empty($fileChanges)) {
            $this->changes[$filePath] = $fileChanges;
            
            if (!$this->dryRun && $content !== $originalContent) {
                file_put_contents($filePath, $content);
                echo "✓ Updated: $filePath\n";
            } else {
                echo "📝 Would update: $filePath\n";
            }
        }
    }

    private function getReplacementPatterns($alias, $replacement)
    {
        $patterns = [];
        $escapedAlias = preg_quote($alias, '/');
        $fullReplacement = '\\' . $replacement;
        
        // Build regex pattern to check if already replaced (negative lookbehind)
        // This prevents matching if the alias is already part of the full namespace
        $escapedReplacementPath = preg_quote($replacement, '/');
        $notAlreadyReplaced = '(?<!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\\\\)';
        
        // Pattern 1: new ClassName() or new \ClassName() -> new \Full\Namespace(
        // Skip if already has the correct namespace path
        $patterns['/\bnew\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '(?:\s|\())\\\\?' . $escapedAlias . '\s*\(/'] = 'new ' . $fullReplacement . '(';
        
        // Pattern 2: ClassName::method() or \ClassName::method() -> \Full\Namespace::method()
        // Skip if already has the correct namespace path
        $patterns['/(?<![\\\\a-zA-Z_])(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '::)\\\\?' . $escapedAlias . '::/'] = $fullReplacement . '::';
        
        // Pattern 3: instanceof ClassName or instanceof \ClassName -> instanceof \Full\Namespace
        $patterns['/\binstanceof\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\b)\\\\?' . $escapedAlias . '\b/'] = 'instanceof ' . $fullReplacement;
        
        // Pattern 4: extends ClassName or extends \ClassName -> extends \Full\Namespace
        $patterns['/\bextends\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\b)\\\\?' . $escapedAlias . '\b/'] = 'extends ' . $fullReplacement;
        
        // Pattern 5: implements ClassName or implements \ClassName -> implements \Full\Namespace
        $patterns['/\bimplements\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\b)\\\\?' . $escapedAlias . '\b/'] = 'implements ' . $fullReplacement;
        
        // Pattern 6: use ClassName; or use \ClassName; -> use \Full\Namespace;
        $patterns['/\buse\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\s*;)\\\\?' . $escapedAlias . '\s*;/'] = 'use ' . $fullReplacement . ';';
        
        // Pattern 7: use ClassName as -> use \Full\Namespace as
        $patterns['/\buse\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\s+as)\\\\?' . $escapedAlias . '\s+as\s+/'] = 'use ' . $fullReplacement . ' as ';
        
        // Pattern 8: @var ClassName or @var \ClassName -> @var \Full\Namespace
        $patterns['/@var\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\b)\\\\?' . $escapedAlias . '\b/'] = '@var ' . $fullReplacement;
        
        // Pattern 9: @param ClassName or @param \ClassName -> @param \Full\Namespace
        $patterns['/@param\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\b)\\\\?' . $escapedAlias . '\b/'] = '@param ' . $fullReplacement;
        
        // Pattern 10: @return ClassName or @return \ClassName -> @return \Full\Namespace
        $patterns['/@return\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\b)\\\\?' . $escapedAlias . '\b/'] = '@return ' . $fullReplacement;
        
        // Pattern 11: function foo(ClassName or function foo(\ClassName -> function foo(\Full\Namespace
        $patterns['/\(\s*(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\s)\\\\?' . $escapedAlias . '\s+/'] = '(' . $fullReplacement . ' ';
        
        // Pattern 12: : ClassName) or : \ClassName) -> : \Full\Namespace) (return type)
        $patterns['/:\s*(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\s*\))\\\\?' . $escapedAlias . '\s*\)/'] = ': ' . $fullReplacement . ')';
        
        // Pattern 13: catch (ClassName or catch (\ClassName -> catch (\Full\Namespace
        $patterns['/\bcatch\s*\(\s*(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\b)\\\\?' . $escapedAlias . '\b/'] = 'catch (' . $fullReplacement;
        
        // Pattern 14: throw new ClassName or throw new \ClassName -> throw new \Full\Namespace
        $patterns['/\bthrow\s+new\s+(?!' . str_replace('\\\\', '\\\\\\\\', $escapedReplacementPath) . '\s*\()\\\\?' . $escapedAlias . '\s*\(/'] = 'throw new ' . $fullReplacement . '(';
        
        return $patterns;
    }

    private function getLineNumber($content, $offset)
    {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }

    private function showSummary()
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "SUMMARY\n";
        echo str_repeat("=", 80) . "\n";

        if (empty($this->changes)) {
            echo "No changes found.\n";
            return;
        }

        $totalFiles = count($this->changes);
        $totalChanges = 0;

        foreach ($this->changes as $filePath => $changes) {
            $totalChanges += count($changes);
            echo "\nFile: $filePath\n";
            echo str_repeat("-", strlen($filePath) + 6) . "\n";
            
            foreach ($changes as $change) {
                echo "  Line {$change['line']}: {$change['original']} → {$change['replacement']}\n";
            }
        }

        echo "\n" . str_repeat("-", 80) . "\n";
        echo "Total files: $totalFiles\n";
        echo "Total changes: $totalChanges\n";
        
        if ($this->dryRun) {
            echo "\nThis was a DRY RUN. No actual changes were made.\n";
            echo "Run without --dry-run to apply these changes.\n";
        } else {
            echo "\nChanges have been applied successfully.\n";
        }
    }
}

// Main execution
try {
    $replacer = new AliasReplacer(array_slice($argv, 1));
    $replacer->run();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
