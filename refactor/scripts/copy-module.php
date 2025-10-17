#!/usr/bin/env php
<?php
/**
 * Copy and transform a module from modules/ to src/Modules/
 * 
 * This script automates the migration of legacy modules to PSR-4 structure:
 * - Copies directory structure
 * - Renames directories (models → Models, views → Views, etc.)
 * - Adds namespace declarations
 * - Updates class names (removes underscores)
 * - Updates use statements and internal references
 * 
 * Usage: php refactor/scripts/copy-module.php ModuleName [--dry-run]
 * 
 * @author App Modernization Team
 */

// Configuration
define('ROOT_DIR', dirname(__DIR__, 2));
define('SOURCE_BASE', ROOT_DIR . '/modules/');
define('DEST_BASE', ROOT_DIR . '/src/Modules/');

// Parse arguments
$moduleName = $argv[1] ?? null;
$dryRun = in_array('--dry-run', $argv);

if (!$moduleName) {
	die("Usage: php copy-module.php ModuleName [--dry-run]\n");
}

// Validate source exists
$sourcePath = SOURCE_BASE . $moduleName;
if (!is_dir($sourcePath)) {
	die("Error: Source module not found: {$sourcePath}\n");
}

// Check if destination already exists
$destPath = DEST_BASE . $moduleName;
if (is_dir($destPath) && !$dryRun) {
	die("Error: Destination already exists: {$destPath}\nDelete it first or use --dry-run to preview.\n");
}

echo "=== Module Migration Tool ===\n";
echo "Module: {$moduleName}\n";
echo "Source: {$sourcePath}\n";
echo "Dest: {$destPath}\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no changes)" : "LIVE") . "\n\n";

// Directory renames mapping
$dirRenames = [
	'models' => 'Models',
	'views' => 'Views',
	'actions' => 'Actions',
	'uitypes' => 'UiTypes',
	'widgets' => 'Widgets',
	'dashboards' => 'Dashboards',
	'handlers' => 'Handlers',
];

/**
 * Recursively copy directory
 */
function recursiveCopy($src, $dst, $dryRun = false) {
	if (!$dryRun) {
		mkdir($dst, 0755, true);
	}
	
	$files = scandir($src);
	foreach ($files as $file) {
		if ($file === '.' || $file === '..') continue;
		
		$srcFile = $src . '/' . $file;
		$dstFile = $dst . '/' . $file;
		
		if (is_dir($srcFile)) {
			echo "  Dir: {$file}/\n";
			recursiveCopy($srcFile, $dstFile, $dryRun);
		} else {
			echo "  File: {$file}\n";
			if (!$dryRun) {
				copy($srcFile, $dstFile);
			}
		}
	}
}

/**
 * Rename directories to PSR-4 conventions
 */
function renameDirectories($baseDir, $renames, $dryRun = false) {
	global $dirRenames;
	
	echo "\n--- Renaming directories ---\n";
	
	foreach ($renames as $old => $new) {
		$oldPath = $baseDir . '/' . $old;
		$newPath = $baseDir . '/' . $new;
		
		if (is_dir($oldPath)) {
			echo "  {$old}/ → {$new}/\n";
			if (!$dryRun) {
				rename($oldPath, $newPath);
			}
		}
	}
}

/**
 * Transform PHP files to PSR-4
 */
function transformFiles($baseDir, $moduleName, $dryRun = false) {
	echo "\n--- Transforming PHP files ---\n";
	
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($baseDir),
		RecursiveIteratorIterator::SELF_FIRST
	);
	
	$phpFiles = [];
	foreach ($iterator as $file) {
		if ($file->isFile() && $file->getExtension() === 'php') {
			$phpFiles[] = $file->getPathname();
		}
	}
	
	echo "Found " . count($phpFiles) . " PHP files to transform\n\n";
	
	foreach ($phpFiles as $filePath) {
		transformPhpFile($filePath, $moduleName, $baseDir, $dryRun);
	}
}

/**
 * Transform individual PHP file
 */
function transformPhpFile($filePath, $moduleName, $baseDir, $dryRun) {
	$relativePath = str_replace($baseDir . '/', '', $filePath);
	echo "  Transforming: {$relativePath}\n";
	
	$content = file_get_contents($filePath);
	$originalContent = $content;
	
	// Detect current class name and type
	if (preg_match('/^class\s+(\w+)/m', $content, $matches)) {
		$oldClassName = $matches[1];
		echo "    Old class: {$oldClassName}\n";
		
		// Parse class name: Module_Component_Type → determine namespace
		$parts = explode('_', $oldClassName);
		
		// Determine namespace from file path
		$pathParts = explode('/', dirname($relativePath));
		$namespace = 'App\\Modules\\' . $moduleName;
		
		// Add subdirectory to namespace (Models, Views, Actions, etc.)
		$subDir = end($pathParts);
		if ($subDir !== $moduleName && preg_match('/^[A-Z]/', $subDir)) {
			$namespace .= '\\' . $subDir;
		}
		
		// New class name (last part, without underscores)
		$newClassName = end($parts);
		
		echo "    New namespace: {$namespace}\n";
		echo "    New class: {$newClassName}\n";
		
		// Add namespace declaration after opening PHP tag
		if (!preg_match('/^namespace\s+/m', $content)) {
			$content = preg_replace(
				'/<\?php\s*\n(\/\*.*?\*\/\s*\n)?/',
				"<?php\n$1\nnamespace {$namespace};\n\n",
				$content,
				1
			);
		}
		
		// Update class name
		$content = preg_replace(
			'/^class\s+' . preg_quote($oldClassName, '/') . '\s+/m',
			"class {$newClassName} ",
			$content
		);
		
		// Update extends clause (remove underscores from parent class)
		$content = preg_replace_callback(
			'/extends\s+(\w+_\w+_\w+)/',
			function($matches) {
				$parentClass = $matches[1];
				// Convert Vtiger_Record_Model → Record (assuming use statement)
				$parts = explode('_', $parentClass);
				return 'extends ' . end($parts);
			},
			$content
		);
		
		// Add use statements for common classes (basic implementation)
		$useStatements = [];
		
		// Check for common parent classes
		if (preg_match('/extends\s+(Record|Module|Field|Base)/', $content)) {
			// Will need manual refinement
		}
		
		// Update Vtiger_Loader references
		$content = str_replace('Vtiger_Loader::', '\\App\\Loader::', $content);
		
		// Save transformed content
		if (!$dryRun && $content !== $originalContent) {
			file_put_contents($filePath, $content);
			echo "    ✓ Transformed\n";
		} elseif ($dryRun) {
			echo "    ~ Would transform (dry run)\n";
		} else {
			echo "    - No changes needed\n";
		}
	} else {
		echo "    - No class found, skipping\n";
	}
}

// Execute migration
echo "--- Step 1: Copying files ---\n";
recursiveCopy($sourcePath, $destPath, $dryRun);

if (!$dryRun) {
	echo "\n--- Step 2: Renaming directories ---\n";
	renameDirectories($destPath, $dirRenames, $dryRun);
	
	echo "\n--- Step 3: Transforming PHP files ---\n";
	transformFiles($destPath, $moduleName, $dryRun);
}

echo "\n=== Migration Complete ===\n";
if ($dryRun) {
	echo "This was a DRY RUN. No actual changes were made.\n";
	echo "Run without --dry-run to perform the migration.\n";
} else {
	echo "Module {$moduleName} migrated to {$destPath}\n";
	echo "\nNext steps:\n";
	echo "1. php refactor/scripts/validate-module.php {$moduleName}\n";
	echo "2. Manual review and fix any issues\n";
	echo "3. php refactor/scripts/test-module.php {$moduleName}\n";
	echo "4. git add src/Modules/{$moduleName}\n";
	echo "5. git commit -m \"Migrate {$moduleName} to PSR-4\"\n";
}

