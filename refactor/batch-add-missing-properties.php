#!/usr/bin/env php
<?php
/**
 * Batch process all PHP files in a directory to add missing properties
 * 
 * Usage: 
 *   php batch-add-missing-properties.php --dry-run <directory>  # Preview changes
 *   php batch-add-missing-properties.php <directory>            # Apply changes
 * 
 * Examples:
 *   php batch-add-missing-properties.php --dry-run src/events/
 *   php batch-add-missing-properties.php src/events/
 */

$dryRun = false;
$directory = null;

// Parse arguments
foreach ($argv as $i => $arg) {
    if ($i === 0) continue; // Skip script name
    
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (!$directory) {
        $directory = $arg;
    }
}

if (!$directory) {
    echo "Usage: php batch-add-missing-properties.php [--dry-run] <directory>\n\n";
    echo "Examples:\n";
    echo "  php batch-add-missing-properties.php --dry-run src/events/\n";
    echo "  php batch-add-missing-properties.php src/events/\n";
    exit(1);
}

if (!is_dir($directory)) {
    echo "Error: Directory not found: $directory\n";
    exit(1);
}

echo "==================================================\n";
if ($dryRun) {
    echo "DRY RUN MODE - No files will be modified\n";
} else {
    echo "PROCESSING MODE - Files will be modified\n";
}
echo "==================================================\n\n";
echo "Processing directory: $directory\n\n";

// Find all PHP files
$phpFiles = [];
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

$totalFiles = count($phpFiles);
$filesWithChanges = 0;
$totalProperties = 0;
$results = [];

echo "Found $totalFiles PHP files to process\n\n";

// Process each file
foreach ($phpFiles as $index => $file) {
    $current = $index + 1;
    echo "[$current/$totalFiles] Processing: $file\n";
    
    // Run the add-missing-properties script
    $cmd = 'php refactor/add-missing-properties.php ' . ($dryRun ? '--dry-run ' : '') . escapeshellarg($file) . ' 2>&1';
    $output = shell_exec($cmd);
    
    // Check if properties were found
    if (preg_match('/Found (\d+) undefined properties in class: (\w+)/', $output, $matches)) {
        $count = (int)$matches[1];
        $className = $matches[2];
        
        $filesWithChanges++;
        $totalProperties += $count;
        
        echo "  ✅ Found $count undefined properties in class $className\n";
        
        // Extract property declarations
        if (preg_match_all('/\/\*\* @var .+ \*\/\n\s+protected \$\w+;/', $output, $propMatches)) {
            foreach ($propMatches[0] as $prop) {
                echo "    " . trim(str_replace("\n", "\n    ", $prop)) . "\n";
            }
        }
        
        $results[$file] = [
            'classes' => [$className],
            'properties' => $count,
            'status' => $dryRun ? 'preview' : 'added'
        ];
    } else {
        echo "  ✓ No undefined properties\n";
        $results[$file] = [
            'classes' => [],
            'properties' => 0,
            'status' => 'clean'
        ];
    }
    echo "\n";
}

// Summary
echo "==================================================\n";
echo "SUMMARY\n";
echo "==================================================\n";
echo "Total files processed: $totalFiles\n";
echo "Files with undefined properties: $filesWithChanges\n";
echo "Total properties " . ($dryRun ? "found" : "added") . ": $totalProperties\n";

if ($dryRun) {
    echo "\n";
    echo "This was a DRY RUN. Run without --dry-run to apply changes.\n";
} else {
    echo "\n";
    echo "✅ All changes have been applied!\n";
    echo "Run PHPStan on the directory to verify:\n";
    echo "  vendor/bin/phpstan analyse $directory --level=5\n";
}

// Detailed results
if ($filesWithChanges > 0) {
    echo "\n==================================================\n";
    echo "FILES WITH CHANGES\n";
    echo "==================================================\n";
    
    foreach ($results as $file => $info) {
        if ($info['properties'] > 0) {
            echo "  $file\n";
            echo "    Properties: {$info['properties']}\n";
            echo "    Classes: " . implode(', ', $info['classes']) . "\n";
        }
    }
}

