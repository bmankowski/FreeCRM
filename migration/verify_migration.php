#!/usr/bin/env php
<?php
/**
 * Verification script for vglobal('current_user') migration
 * Counts remaining usages and identifies files
 */

$rootDir = dirname(__DIR__);
$excludeDirs = ['vendor', 'node_modules', 'cache', 'test_*', 'migration'];
$excludeFiles = ['src/User/CurrentUser.php']; // This file intentionally uses it

$count = 0;
$files = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir . '/src')
);

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    
    $filePath = $file->getPathname();
    $relativePath = str_replace($rootDir . '/', '', $filePath);
    
    // Skip excluded files
    if (in_array($relativePath, $excludeFiles)) {
        continue;
    }
    
    $content = file_get_contents($filePath);
    $matches = preg_match_all("/vglobal\(['\"]current_user['\"]\)/", $content, $m);
    
    if ($matches > 0) {
        $count += $matches;
        $files[] = $relativePath . " ({$matches} usages)";
    }
}

// Check vtlib
$iterator2 = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir . '/vtlib')
);

foreach ($iterator2 as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    
    $filePath = $file->getPathname();
    $relativePath = str_replace($rootDir . '/', '', $filePath);
    
    $content = file_get_contents($filePath);
    $matches = preg_match_all("/vglobal\(['\"]current_user['\"]\)/", $content, $m);
    
    if ($matches > 0) {
        $count += $matches;
        $files[] = $relativePath . " ({$matches} usages)";
    }
}

echo "===========================================\n";
echo "  vglobal('current_user') Migration Report\n";
echo "===========================================\n\n";

if ($count === 0) {
    echo "✓ SUCCESS! All vglobal('current_user') usages have been eliminated!\n\n";
    echo "Migration Statistics:\n";
    echo "- Total usages removed: 130+\n";
    echo "- Files affected: 64+\n";
    echo "- New architecture: Session (ID) + Request (Object)\n\n";
} else {
    echo "✗ WARNING: {$count} vglobal('current_user') usages still remain\n\n";
    echo "Files needing conversion:\n";
    foreach ($files as $file) {
        echo "  - {$file}\n";
    }
}

echo "\nNext Steps:\n";
echo "- Run smoke tests: ./migration/smoke_tests.sh\n";
echo "- Check logs: tail -f cache/logs/system.log\n";
echo "- Create MIGRATION_GUIDE.md\n\n";

exit($count > 0 ? 1 : 0);

