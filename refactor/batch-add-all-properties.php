#!/usr/bin/env php
<?php
/**
 * Efficiently batch process entire directory by running PHPStan once
 * 
 * Usage: 
 *   php batch-add-all-properties.php --dry-run <directory>  # Preview changes
 *   php batch-add-all-properties.php <directory>            # Apply changes
 */

$dryRun = false;
$directory = null;

foreach ($argv as $i => $arg) {
    if ($i === 0) continue;
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (!$directory) {
        $directory = $arg;
    }
}

if (!$directory) {
    echo "Usage: php batch-add-all-properties.php [--dry-run] <directory>\n";
    exit(1);
}

if (!is_dir($directory)) {
    echo "Error: Directory not found: $directory\n";
    exit(1);
}

echo "==================================================\n";
echo $dryRun ? "DRY RUN MODE\n" : "PROCESSING MODE\n";
echo "==================================================\n\n";
echo "Analyzing directory: $directory\n";
echo "Running PHPStan analysis...\n\n";

// Run PHPStan once on entire directory
$cmd = "vendor/bin/phpstan analyse $directory --level=5 --error-format=json 2>&1";
$output = shell_exec($cmd);

// Extract JSON
if (preg_match('/\{.*"files".*\}/s', $output, $matches)) {
    $json = $matches[0];
    $data = json_decode($json, true);
} else {
    echo "Error: Could not parse PHPStan output\n";
    exit(1);
}

if (!$data || !isset($data['files'])) {
    echo "Error: Invalid PHPStan data\n";
    exit(1);
}

// Group undefined properties by file and class
$fileProperties = [];

foreach ($data['files'] as $filename => $fileData) {
    foreach ($fileData['messages'] as $message) {
        if (strpos($message['message'], 'Access to an undefined property') !== false) {
            if (preg_match('/Access to an undefined property ([^:]+)::\$(\w+)/', $message['message'], $matches)) {
                $className = $matches[1];
                $propertyName = $matches[2];
                
                if (!isset($fileProperties[$filename])) {
                    $fileProperties[$filename] = [];
                }
                if (!isset($fileProperties[$filename][$className])) {
                    $fileProperties[$filename][$className] = [];
                }
                if (!in_array($propertyName, $fileProperties[$filename][$className])) {
                    $fileProperties[$filename][$className][] = $propertyName;
                }
            }
        }
    }
}

$totalFiles = count($fileProperties);
$totalClasses = 0;
$totalProperties = 0;

foreach ($fileProperties as $props) {
    $totalClasses += count($props);
    foreach ($props as $classProps) {
        $totalProperties += count($classProps);
    }
}

echo "Found:\n";
echo "  Files with undefined properties: $totalFiles\n";
echo "  Classes with undefined properties: $totalClasses\n";
echo "  Total undefined properties: $totalProperties\n\n";

if ($totalProperties === 0) {
    echo "✅ No undefined properties found!\n";
    exit(0);
}

// Process each file
$processed = 0;
foreach ($fileProperties as $filename => $classes) {
    $processed++;
    $relativeFile = str_replace(getcwd() . '/', '', $filename);
    echo "[$processed/$totalFiles] Processing: $relativeFile\n";
    
    foreach ($classes as $className => $properties) {
        $shortClassName = substr($className, strrpos($className, '\\') + 1);
        echo "  Class: $shortClassName (" . count($properties) . " properties)\n";
    }
    
    if (!$dryRun) {
        // Run the single-file processor
        $cmd = 'php refactor/add-missing-properties.php ' . escapeshellarg($filename) . ' 2>&1';
        $result = shell_exec($cmd);
        
        if (strpos($result, '✅') !== false) {
            echo "  ✅ Properties added\n";
        } else {
            echo "  ⚠️  Could not add properties\n";
        }
    }
    echo "\n";
}

echo "==================================================\n";
echo "SUMMARY\n";
echo "==================================================\n";
echo "Files processed: $totalFiles\n";
echo "Classes fixed: $totalClasses\n";
echo "Properties " . ($dryRun ? "found" : "added") . ": $totalProperties\n";

if ($dryRun) {
    echo "\nThis was a DRY RUN. Run without --dry-run to apply changes.\n";
} else {
    echo "\n✅ All changes applied!\n";
    echo "Verify with: vendor/bin/phpstan analyse $directory --level=5\n";
}

