#!/usr/bin/env php
<?php
/**
 * Find and split PHP files with multiple class/interface/abstract class declarations
 * Follows PSR-4: One class per file
 * 
 * Usage:
 *   php find_files_with_many_classes.php --dry-run <directory>  # List files only
 *   php find_files_with_many_classes.php <directory>            # Split files
 * 
 * Examples:
 *   php find_files_with_many_classes.php --dry-run src/events/
 *   php find_files_with_many_classes.php src/events/
 */

$dryRun = false;
$directory = null;

// Parse arguments
foreach ($argv as $i => $arg) {
    if ($i === 0) continue;
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (!$directory) {
        $directory = $arg;
    }
}

if (!$directory) {
    echo "Usage: php find_files_with_many_classes.php [--dry-run] <directory>\n\n";
    echo "Examples:\n";
    echo "  php find_files_with_many_classes.php --dry-run src/events/\n";
    echo "  php find_files_with_many_classes.php src/events/\n";
    exit(1);
}

// Handle both files and directories
$phpFiles = [];

if (is_file($directory)) {
    // Single file
    if (pathinfo($directory, PATHINFO_EXTENSION) === 'php') {
        $phpFiles[] = $directory;
    } else {
        echo "Error: File must be a .php file\n";
        exit(1);
    }
    $scanType = "file";
} elseif (is_dir($directory)) {
    // Directory
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $phpFiles[] = $file->getPathname();
        }
    }
    sort($phpFiles);
    $scanType = "directory";
} else {
    echo "Error: Not a valid file or directory: $directory\n";
    exit(1);
}

echo "==================================================\n";
echo $dryRun ? "DRY RUN MODE - No files will be modified\n" : "PROCESSING MODE - Files will be split\n";
echo "==================================================\n\n";
echo "Scanning $scanType: $directory\n\n";

// Analyze files for multiple classes
$filesWithMultipleClasses = [];
$totalClasses = 0;

foreach ($phpFiles as $file) {
    $classes = detectClasses($file);
    
    if (count($classes) > 1) {
        $filesWithMultipleClasses[$file] = $classes;
        $totalClasses += count($classes);
    }
}

echo "Found:\n";
echo "  Total PHP files: " . count($phpFiles) . "\n";
echo "  Files with multiple classes: " . count($filesWithMultipleClasses) . "\n";
echo "  Total classes to split: $totalClasses\n\n";

if (empty($filesWithMultipleClasses)) {
    echo "No files with multiple classes found!\n";
    exit(0);
}

// Display files
echo "Files with multiple classes:\n";
echo str_repeat("-", 70) . "\n";

foreach ($filesWithMultipleClasses as $file => $classes) {
    $relativeFile = str_replace(getcwd() . '/', '', $file);
    echo "\n$relativeFile (" . count($classes) . " declarations):\n";
    
    foreach ($classes as $idx => $class) {
        $num = $idx + 1;
        echo "  $num. {$class['type']} {$class['name']} (line {$class['line']})\n";
    }
    
    if (!$dryRun) {
        echo "  Splitting...\n";
        $result = splitFile($file, $classes);
        
        if ($result['success']) {
            echo "  ✅ Split into " . count($result['files']) . " files:\n";
            foreach ($result['files'] as $newFile) {
                echo "     - " . basename($newFile) . "\n";
            }
            
            // Update references
            if (!empty($result['oldFile'])) {
                $updated = updateReferences($result['oldFile'], $result['files'], $classes);
                if ($updated > 0) {
                    echo "  ✅ Updated $updated file references\n";
                }
            }
        } else {
            echo "  ❌ Failed: " . $result['error'] . "\n";
        }
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 70) . "\n";
echo "Files with multiple classes: " . count($filesWithMultipleClasses) . "\n";
echo "Total classes: $totalClasses\n";

if ($dryRun) {
    echo "\nThis was a DRY RUN. Run without --dry-run to split files.\n";
} else {
    echo "\n✅ All files have been split!\n";
    echo "Note: Review the changes and test thoroughly.\n";
}

/**
 * Detect classes, interfaces, and abstract classes in a file
 */
function detectClasses($file) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $classes = [];
    
    foreach ($lines as $lineNum => $line) {
        // Match class, abstract class, and interface declarations
        if (preg_match('/^(abstract\s+)?(class|interface)\s+(\w+)/', $line, $matches)) {
            $classes[] = [
                'type' => trim($matches[1] . $matches[2]),
                'name' => $matches[3],
                'line' => $lineNum + 1,
                'lineIndex' => $lineNum
            ];
        }
    }
    
    return $classes;
}

/**
 * Split a file with multiple classes into separate files
 */
function splitFile($file, $classes) {
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $directory = dirname($file);
    $createdFiles = [];
    
    try {
        // Extract common elements from original file
        $namespace = extractNamespace($lines);
        $useStatements = extractUseStatements($lines);
        $fileHeader = extractFileHeader($lines);
        
        // Process each class
        foreach ($classes as $idx => $class) {
            $className = $class['name'];
            $newFileName = $directory . '/' . $className . '.php';
            
            // Skip if file already exists (avoid overwriting)
            if (file_exists($newFileName) && $newFileName !== $file) {
                continue; // Will use existing file
            }
            
            // Extract class content
            $classStartLine = $class['lineIndex'];
            $classEndLine = findClassEndLine($lines, $classStartLine);
            
            // Get PHPDoc for this class
            $phpDocStart = findPhpDocStart($lines, $classStartLine);
            
            // Build new file content
            $newContent = [];
            $newContent[] = "<?php";
            
            // Add file header
            if ($fileHeader) {
                $newContent[] = $fileHeader;
            }
            
            // Add namespace
            if ($namespace) {
                $newContent[] = "";
                $newContent[] = $namespace;
            }
            
            // Add use statements
            if ($useStatements) {
                $newContent[] = "";
                foreach ($useStatements as $use) {
                    $newContent[] = $use;
                }
            }
            
            // Add class with its PHPDoc
            $newContent[] = "";
            for ($i = $phpDocStart; $i <= $classEndLine; $i++) {
                $newContent[] = $lines[$i];
            }
            
            // Write file
            file_put_contents($newFileName, implode("\n", $newContent));
            $createdFiles[] = $newFileName;
        }
        
        // Delete original file if it's different from created files
        $originalStillNeeded = false;
        foreach ($createdFiles as $created) {
            if ($created === $file) {
                $originalStillNeeded = true;
                break;
            }
        }
        
        if (!$originalStillNeeded && count($createdFiles) > 0) {
            // Backup before delete
            $backupFile = $file . '.backup';
            copy($file, $backupFile);
            unlink($file);
        }
        
        return [
            'success' => true,
            'files' => $createdFiles,
            'oldFile' => $originalStillNeeded ? null : $file
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Extract namespace declaration
 */
function extractNamespace($lines) {
    foreach ($lines as $line) {
        if (preg_match('/^namespace\s+([^;]+);/', $line, $matches)) {
            return $line;
        }
    }
    return null;
}

/**
 * Extract use statements
 */
function extractUseStatements($lines) {
    $useStatements = [];
    $inUseBlock = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Start collecting after namespace
        if (preg_match('/^namespace\s+/', $line)) {
            $inUseBlock = true;
            continue;
        }
        
        // Stop at first class/interface declaration
        if (preg_match('/^(abstract\s+)?(class|interface)\s+/', $line)) {
            break;
        }
        
        // Collect use statements
        if ($inUseBlock && preg_match('/^use\s+/', $line)) {
            $useStatements[] = $line;
        }
    }
    
    return $useStatements;
}

/**
 * Extract file header (copyright, license, etc.)
 */
function extractFileHeader($lines) {
    $header = [];
    $inComment = false;
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        
        // Skip opening PHP tag
        if ($trimmed === '<?php') {
            continue;
        }
        
        // Collect header comments
        if (preg_match('/^\/\*/', $trimmed)) {
            $inComment = true;
        }
        
        if ($inComment) {
            $header[] = $line;
            if (preg_match('/\*\/\s*$/', $trimmed)) {
                $inComment = false;
            }
            continue;
        }
        
        // Stop at namespace or class
        if (preg_match('/^(namespace|use|class|abstract|interface)\s+/', $trimmed)) {
            break;
        }
        
        // Collect single-line comments
        if (preg_match('/^\/\//', $trimmed) && !empty($trimmed)) {
            $header[] = $line;
        }
    }
    
    return implode("\n", $header);
}

/**
 * Find the end line of a class
 */
function findClassEndLine($lines, $startLine) {
    $braceCount = 0;
    $foundOpenBrace = false;
    
    for ($i = $startLine; $i < count($lines); $i++) {
        $line = $lines[$i];
        
        // Count braces
        for ($j = 0; $j < strlen($line); $j++) {
            if ($line[$j] === '{') {
                $braceCount++;
                $foundOpenBrace = true;
            } elseif ($line[$j] === '}') {
                $braceCount--;
                
                // When braces balance, we found the end
                if ($foundOpenBrace && $braceCount === 0) {
                    return $i;
                }
            }
        }
    }
    
    return count($lines) - 1;
}

/**
 * Find the start of PHPDoc for a class
 */
function findPhpDocStart($lines, $classLine) {
    // Look backwards from class line
    for ($i = $classLine - 1; $i >= 0; $i--) {
        $trimmed = trim($lines[$i]);
        
        // Found end of PHPDoc
        if (preg_match('/\*\/\s*$/', $trimmed)) {
            // Now find the start
            for ($j = $i; $j >= 0; $j--) {
                if (preg_match('/^\/\*\*/', trim($lines[$j]))) {
                    return $j;
                }
            }
            return $i;
        }
        
        // Hit something else (not empty, not comment)
        if (!empty($trimmed) && !preg_match('/^(\/\/|\*)/', $trimmed)) {
            return $classLine;
        }
    }
    
    return $classLine;
}

/**
 * Update references to split files
 */
function updateReferences($oldFile, $newFiles, $classes) {
    $oldBasename = basename($oldFile, '.php');
    $directory = dirname($oldFile);
    $updatedCount = 0;
    
    // Find all PHP files that might reference this file
    $allPhpFiles = [];
    $searchDir = dirname(dirname(dirname($oldFile))); // Go up to src/
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($searchDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $allPhpFiles[] = $file->getPathname();
        }
    }
    
    // Update each file
    foreach ($allPhpFiles as $phpFile) {
        $content = file_get_contents($phpFile);
        $modified = false;
        
        // Update require/include statements
        $patterns = [
            '/require(_once)?\s*\(?\s*[\'"]([^\'"]*)' . preg_quote($oldBasename, '/') . '\.php[\'"]\s*\)?/',
            '/include(_once)?\s*\(?\s*[\'"]([^\'"]*)' . preg_quote($oldBasename, '/') . '\.php[\'"]\s*\)?/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                // For now, just add a comment about needing manual update
                // Full automation would require analyzing which classes are actually used
                $modified = true;
                
                // Add use statements for all classes from split file
                if (!preg_match('/^namespace\s+/m', $content)) {
                    continue; // Skip files without namespace
                }
                
                // Find namespace and add use statements after it
                foreach ($classes as $class) {
                    $className = $class['name'];
                    $namespace = extractNamespaceFromFile($oldFile);
                    
                    if ($namespace) {
                        $fqcn = $namespace . '\\' . $className;
                        
                        // Check if use statement doesn't already exist
                        if (!preg_match('/use\s+' . preg_quote($fqcn, '/') . '\s*;/', $content)) {
                            // Add use statement after namespace
                            $content = preg_replace(
                                '/(^namespace\s+[^;]+;\s*\n)/m',
                                "$1use $fqcn;\n",
                                $content,
                                1
                            );
                        }
                    }
                }
            }
        }
        
        if ($modified) {
            file_put_contents($phpFile, $content);
            $updatedCount++;
        }
    }
    
    return $updatedCount;
}

/**
 * Extract namespace from file
 */
function extractNamespaceFromFile($file) {
    $content = file_get_contents($file);
    if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
        return trim($matches[1]);
    }
    return null;
}

// Execute
echo str_repeat("=", 70) . "\n";

