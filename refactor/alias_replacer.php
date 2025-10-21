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
    private $excludeDirs = ['vendor', 'node_modules', '.git', 'cache', 'storage', 'logs'];

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
        $aliasesFile = __DIR__ . '/src/GlobalAliases.php';
        
        if (!file_exists($aliasesFile)) {
            throw new Exception("GlobalAliases.php not found at: $aliasesFile");
        }

        $content = file_get_contents($aliasesFile);
        
        // Extract the aliases array from the file
        if (preg_match('/\$aliases\s*=\s*\[(.*?)\];/s', $content, $matches)) {
            $aliasesString = $matches[1];
            
            // Parse the array entries
            $lines = explode("\n", $aliasesString);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '//') === 0) {
                    continue;
                }
                
                // Match patterns like 'Vtiger_PDF_Model' => '\App\Modules\Vtiger\Models\PDF',
                if (preg_match("/'([^']+)'\s*=>\s*'([^']+)'/", $line, $matches)) {
                    $this->aliases[$matches[1]] = $matches[2];
                }
            }
        }

        if (empty($this->aliases)) {
            throw new Exception("No aliases found in GlobalAliases.php");
        }

        echo "Loaded " . count($this->aliases) . " aliases:\n";
        foreach ($this->aliases as $key => $value) {
            echo "  $key => $value\n";
        }
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
        // Skip the GlobalAliases.php file itself to avoid modifying the array definition
        if (basename($filePath) === 'GlobalAliases.php') {
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
        
        // Pattern 1: Full class name with leading backslash (but not in array definitions)
        $patterns['/\\\\' . preg_quote($alias, '/') . '\b/'] = '\\' . $replacement;
        
        // Pattern 2: Class name without leading backslash (but with word boundary, not in array definitions)
        $patterns['/\b' . preg_quote($alias, '/') . '\b/'] = $replacement;
        
        // Pattern 3: new ClassName() - handle both with and without leading backslash
        $patterns['/new\s+\\\\?' . preg_quote($alias, '/') . '\s*\(/'] = 'new \\' . $replacement . '(';
        
        // Pattern 4: ClassName::method() - static method calls
        $patterns['/\\\\?' . preg_quote($alias, '/') . '::/'] = '\\' . $replacement . '::';
        
        // Pattern 5: instanceof ClassName
        $patterns['/instanceof\s+\\\\?' . preg_quote($alias, '/') . '\b/'] = 'instanceof \\' . $replacement;
        
        // Pattern 6: ClassName::class
        $patterns['/\\\\?' . preg_quote($alias, '/') . '::class/'] = '\\' . $replacement . '::class';
        
        // Pattern 7: In use statements: use ClassName;
        $patterns['/use\s+\\\\?' . preg_quote($alias, '/') . '\s*;/'] = 'use \\' . $replacement . ';';
        
        // Pattern 8: In use statements with as: use ClassName as Alias;
        $patterns['/use\s+\\\\?' . preg_quote($alias, '/') . '\s+as\s+\w+\s*;/'] = 'use \\' . $replacement . ' as $1;';
        
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
