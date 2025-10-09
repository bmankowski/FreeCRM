#!/usr/bin/env php
<?php
/**
 * Validate migrated module structure and syntax
 * 
 * Checks:
 * - All PHP files have namespace declarations
 * - Class names match file names (PSR-4 compliance)
 * - No PHP syntax errors
 * - Directory structure follows conventions
 * 
 * Usage: php refactor/scripts/validate-module.php ModuleName
 * 
 * @author FreeCRM Modernization Team
 */

// Configuration
define('ROOT_DIR', dirname(__DIR__, 2));
define('MODULES_BASE', ROOT_DIR . '/src/Modules/');

// Parse arguments
$moduleName = $argv[1] ?? null;

if (!$moduleName) {
	die("Usage: php validate-module.php ModuleName\n");
}

$modulePath = MODULES_BASE . $moduleName;

if (!is_dir($modulePath)) {
	die("Error: Module not found: {$modulePath}\n");
}

echo "=== Module Validation Tool ===\n";
echo "Module: {$moduleName}\n";
echo "Path: {$modulePath}\n\n";

$errors = [];
$warnings = [];
$checked = 0;

/**
 * Recursively find all PHP files
 */
function findPhpFiles($dir) {
	$files = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir),
		RecursiveIteratorIterator::SELF_FIRST
	);
	
	foreach ($iterator as $file) {
		if ($file->isFile() && $file->getExtension() === 'php') {
			$files[] = $file->getPathname();
		}
	}
	
	return $files;
}

/**
 * Check PHP syntax
 */
function checkSyntax($file) {
	$output = [];
	$returnCode = 0;
	exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $returnCode);
	return $returnCode === 0;
}

/**
 * Check if file has namespace declaration
 */
function hasNamespace($content) {
	return preg_match('/^namespace\s+[\w\\\\]+;/m', $content);
}

/**
 * Extract namespace from file
 */
function extractNamespace($content) {
	if (preg_match('/^namespace\s+([\w\\\\]+);/m', $content, $matches)) {
		return $matches[1];
	}
	return null;
}

/**
 * Extract class name from file
 */
function extractClassName($content) {
	if (preg_match('/^class\s+(\w+)/m', $content, $matches)) {
		return $matches[1];
	}
	if (preg_match('/^interface\s+(\w+)/m', $content, $matches)) {
		return $matches[1];
	}
	if (preg_match('/^trait\s+(\w+)/m', $content, $matches)) {
		return $matches[1];
	}
	return null;
}

/**
 * Validate expected namespace based on file path
 */
function getExpectedNamespace($filePath, $moduleName) {
	$relativePath = str_replace(MODULES_BASE, '', $filePath);
	$pathParts = explode('/', dirname($relativePath));
	
	// FreeCRM\Modules\ModuleName\SubDir\...
	$namespace = 'FreeCRM\\Modules\\' . $moduleName;
	
	// Add subdirectories (skip the module name itself)
	for ($i = 1; $i < count($pathParts); $i++) {
		if (!empty($pathParts[$i])) {
			$namespace .= '\\' . $pathParts[$i];
		}
	}
	
	return $namespace;
}

// Find all PHP files
echo "--- Finding PHP files ---\n";
$phpFiles = findPhpFiles($modulePath);
echo "Found " . count($phpFiles) . " PHP files\n\n";

// Validate each file
echo "--- Validating files ---\n";

foreach ($phpFiles as $file) {
	$checked++;
	$relativePath = str_replace($modulePath . '/', '', $file);
	$fileName = basename($file, '.php');
	
	// Read file content
	$content = file_get_contents($file);
	
	// Check 1: PHP Syntax
	if (!checkSyntax($file)) {
		$errors[] = "{$relativePath}: Syntax error";
		echo "✗ {$relativePath} - Syntax error\n";
		continue;
	}
	
	// Check 2: Has namespace
	if (!hasNamespace($content)) {
		$errors[] = "{$relativePath}: Missing namespace declaration";
		echo "✗ {$relativePath} - Missing namespace\n";
		continue;
	}
	
	// Check 3: Namespace matches path
	$actualNamespace = extractNamespace($content);
	$expectedNamespace = getExpectedNamespace($file, $moduleName);
	
	if ($actualNamespace !== $expectedNamespace) {
		$warnings[] = "{$relativePath}: Namespace mismatch (expected: {$expectedNamespace}, got: {$actualNamespace})";
		echo "⚠ {$relativePath} - Namespace mismatch\n";
	}
	
	// Check 4: Class name matches file name
	$className = extractClassName($content);
	if ($className && $className !== $fileName) {
		$errors[] = "{$relativePath}: Class name '{$className}' doesn't match file name '{$fileName}'";
		echo "✗ {$relativePath} - Class/file name mismatch\n";
		continue;
	}
	
	// Check 5: Has class/interface/trait definition
	if (!$className) {
		// Entity files (like Users.php, Leads.php) might not follow exact pattern
		if ($fileName === $moduleName) {
			// This is the entity file, might be OK
			echo "~ {$relativePath} - Entity file (no class found)\n";
		} else {
			$warnings[] = "{$relativePath}: No class/interface/trait definition found";
			echo "⚠ {$relativePath} - No class definition\n";
		}
		continue;
	}
	
	echo "✓ {$relativePath}\n";
}

// Summary
echo "\n=== Validation Summary ===\n";
echo "Files checked: {$checked}\n";
echo "Errors: " . count($errors) . "\n";
echo "Warnings: " . count($warnings) . "\n\n";

if (!empty($errors)) {
	echo "=== Errors ===\n";
	foreach ($errors as $error) {
		echo "  - {$error}\n";
	}
	echo "\n";
}

if (!empty($warnings)) {
	echo "=== Warnings ===\n";
	foreach ($warnings as $warning) {
		echo "  - {$warning}\n";
	}
	echo "\n";
}

// Exit code
if (!empty($errors)) {
	echo "❌ Validation FAILED\n";
	exit(1);
} elseif (!empty($warnings)) {
	echo "⚠️  Validation passed with warnings\n";
	exit(0);
} else {
	echo "✅ Validation PASSED\n";
	exit(0);
}

