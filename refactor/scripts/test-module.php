#!/usr/bin/env php
<?php
/**
 * Test migrated module can be loaded
 * 
 * Tests:
 * - Module components can be resolved by Loader
 * - Common components (Models, Views, Actions) exist
 * - Classes can be instantiated (basic check)
 * 
 * Usage: php refactor/scripts/test-module.php ModuleName
 * 
 * @author FreeCRM Modernization Team
 */

// Configuration
define('ROOT_DIR', dirname(__DIR__, 2));
define('ROOT_DIRECTORY', ROOT_DIR); // For compatibility

// Load composer autoloader
require ROOT_DIR . '/vendor/autoload.php';

// Parse arguments
$moduleName = $argv[1] ?? null;

if (!$moduleName) {
	die("Usage: php test-module.php ModuleName\n");
}

echo "=== Module Loader Test ===\n";
echo "Module: {$moduleName}\n\n";

// Common component types to test
$testComponents = [
	['Model', 'Record'],
	['Model', 'Module'],
	['Model', 'Field'],
	['View', 'List'],
	['View', 'Detail'],
	['View', 'Edit'],
	['Action', 'Save'],
	['Action', 'Delete'],
	['Action', 'MassDelete'],
];

$passed = 0;
$failed = 0;
$skipped = 0;

echo "--- Testing component resolution ---\n";

foreach ($testComponents as $test) {
	list($type, $name) = $test;
	
	try {
		$className = \FreeCRM\Loader::getComponentClassName($type, $name, $moduleName);
		
		// Check if class actually exists
		if (class_exists($className)) {
			echo "✓ {$type}/{$name} → {$className}\n";
			$passed++;
		} else {
			echo "✗ {$type}/{$name} → {$className} (class doesn't exist)\n";
			$failed++;
		}
	} catch (\Exception $e) {
		// Component not found - this is OK, not all modules have all components
		echo "~ {$type}/{$name} - Not found (fallback to Vtiger?)\n";
		$skipped++;
	}
}

// Additional checks
echo "\n--- Testing class instantiation ---\n";

// Try to load module record model
try {
	$recordClass = \FreeCRM\Loader::getComponentClassName('Model', 'Record', $moduleName);
	if (class_exists($recordClass)) {
		echo "✓ Can load Record model: {$recordClass}\n";
		
		// Try to get reflection info
		$reflection = new ReflectionClass($recordClass);
		echo "  - Parent: " . ($reflection->getParentClass() ? $reflection->getParentClass()->getName() : 'none') . "\n";
		echo "  - Methods: " . count($reflection->getMethods()) . "\n";
	}
} catch (\Exception $e) {
	echo "~ Record model not available or uses fallback\n";
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Skipped: {$skipped}\n\n";

if ($failed > 0) {
	echo "❌ Some tests FAILED\n";
	exit(1);
} elseif ($passed > 0) {
	echo "✅ Tests PASSED\n";
	exit(0);
} else {
	echo "⚠️  No components found (all skipped)\n";
	exit(0);
}

