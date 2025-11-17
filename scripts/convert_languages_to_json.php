<?php
/**
 * Language Files Converter: PHP to JSON
 * 
 * Converts all language files from PHP format to JSON format compatible with YetiForce.
 * 
 * Usage: php scripts/convert_languages_to_json.php [--dry-run] [--remove-php]
 * 
 * Options:
 *   --dry-run     Show what would be converted without actually converting
 *   --remove-php  Remove PHP files after successful conversion
 */

// Determine project root directory
$rootDirectory = dirname(__DIR__);
chdir($rootDirectory);

// Bootstrap minimal environment
if (!defined('ROOT_DIRECTORY')) {
	define('ROOT_DIRECTORY', $rootDirectory);
}

// Parse command line arguments
$dryRun = in_array('--dry-run', $argv);
$removePhp = in_array('--remove-php', $argv);

echo "Language Files Converter: PHP to JSON\n";
echo "=====================================\n\n";

if ($dryRun) {
	echo "DRY RUN MODE - No files will be modified\n\n";
}

/**
 * Extract arrays from PHP file
 * 
 * @param string $phpFile Path to PHP file
 * @return array|null Array with 'languageStrings' and 'jsLanguageStrings' keys, or null on error
 */
function extractArraysFromPhp($phpFile)
{
	if (!file_exists($phpFile)) {
		return null;
	}
	
	$languageStrings = [];
	$jsLanguageStrings = [];
	
	// Include the PHP file to get the arrays
	// We need to isolate the scope to avoid conflicts
	$content = file_get_contents($phpFile);
	
	// Extract arrays using regex (more reliable than require)
	// Match $languageStrings = [...];
	if (preg_match('/\$languageStrings\s*=\s*\[(.*?)\];/s', $content, $matches)) {
		// Evaluate the array content safely
		$arrayContent = $matches[1];
		// Use eval in isolated scope - this is safe because we control the input
		// and these are language files with known structure
		try {
			eval('$languageStrings = [' . $arrayContent . '];');
		} catch (Throwable $e) {
			echo "  WARNING: Failed to parse languageStrings in {$phpFile}: " . $e->getMessage() . "\n";
			$languageStrings = [];
		}
	}
	
	// Match $jsLanguageStrings = [...];
	if (preg_match('/\$jsLanguageStrings\s*=\s*\[(.*?)\];/s', $content, $matches)) {
		$arrayContent = $matches[1];
		try {
			eval('$jsLanguageStrings = [' . $arrayContent . '];');
		} catch (Throwable $e) {
			echo "  WARNING: Failed to parse jsLanguageStrings in {$phpFile}: " . $e->getMessage() . "\n";
			$jsLanguageStrings = [];
		}
	}
	
	return [
		'languageStrings' => $languageStrings,
		'jsLanguageStrings' => $jsLanguageStrings
	];
}

/**
 * Convert PHP file to JSON
 * 
 * @param string $phpFile Path to PHP file
 * @param bool $dryRun If true, don't write files
 * @return bool True on success, false on failure
 */
function convertPhpToJson($phpFile, $dryRun = false)
{
	$data = extractArraysFromPhp($phpFile);
	if ($data === null) {
		return false;
	}
	
	$jsonFile = str_replace('.php', '.json', $phpFile);
	
	// Prepare JSON data
	$jsonData = [
		'languageStrings' => $data['languageStrings'],
		'jsLanguageStrings' => $data['jsLanguageStrings']
	];
	
	// Encode to JSON with pretty printing
	$jsonContent = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	
	if ($jsonContent === false) {
		echo "  ERROR: Failed to encode JSON for {$phpFile}\n";
		return false;
	}
	
	if (!$dryRun) {
		// Ensure directory exists
		$dir = dirname($jsonFile);
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
		
		// Write JSON file
		if (file_put_contents($jsonFile, $jsonContent) === false) {
			echo "  ERROR: Failed to write {$jsonFile}\n";
			return false;
		}
	}
	
	// Validate conversion
	$phpKeys = count($data['languageStrings']) + count($data['jsLanguageStrings']);
	$jsonKeys = count($jsonData['languageStrings']) + count($jsonData['jsLanguageStrings']);
	
	if ($phpKeys !== $jsonKeys) {
		echo "  WARNING: Key count mismatch - PHP: {$phpKeys}, JSON: {$jsonKeys}\n";
		return false;
	}
	
	return true;
}

/**
 * Find all PHP language files recursively
 * 
 * @param string $directory Directory to search
 * @return array Array of PHP file paths
 */
function findPhpLanguageFiles($directory)
{
	$files = [];
	
	if (!is_dir($directory)) {
		return $files;
	}
	
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST
	);
	
	foreach ($iterator as $file) {
		if ($file->isFile() && $file->getExtension() === 'php') {
			$files[] = $file->getPathname();
		}
	}
	
	return $files;
}

// Main conversion process
$directories = [
	ROOT_DIRECTORY . '/languages',
	ROOT_DIRECTORY . '/custom/languages'
];

$totalFiles = 0;
$convertedFiles = 0;
$failedFiles = 0;
$skippedFiles = 0;

foreach ($directories as $dir) {
	if (!is_dir($dir)) {
		echo "Skipping non-existent directory: {$dir}\n";
		continue;
	}
	
	echo "Processing directory: {$dir}\n";
	$phpFiles = findPhpLanguageFiles($dir);
	
	foreach ($phpFiles as $phpFile) {
		$totalFiles++;
		$relativePath = str_replace(ROOT_DIRECTORY . '/', '', $phpFile);
		$jsonFile = str_replace('.php', '.json', $relativePath);
		
		// Skip if JSON file already exists
		if (file_exists(ROOT_DIRECTORY . '/' . $jsonFile)) {
			echo "  SKIP: {$relativePath} (JSON already exists)\n";
			$skippedFiles++;
			continue;
		}
		
		echo "  Converting: {$relativePath}";
		
		if (convertPhpToJson($phpFile, $dryRun)) {
			echo " -> {$jsonFile} ✓\n";
			$convertedFiles++;
			
			// Remove PHP file if requested
			if ($removePhp && !$dryRun) {
				if (unlink($phpFile)) {
					echo "    Removed PHP file: {$relativePath}\n";
				} else {
					echo "    WARNING: Failed to remove PHP file: {$relativePath}\n";
				}
			}
		} else {
			echo " -> FAILED ✗\n";
			$failedFiles++;
		}
	}
	
	echo "\n";
}

// Summary
echo "=====================================\n";
echo "Conversion Summary:\n";
echo "  Total files found: {$totalFiles}\n";
echo "  Converted: {$convertedFiles}\n";
echo "  Skipped (JSON exists): {$skippedFiles}\n";
echo "  Failed: {$failedFiles}\n";

if ($dryRun) {
	echo "\nThis was a dry run. No files were modified.\n";
	echo "Run without --dry-run to perform actual conversion.\n";
} elseif ($removePhp) {
	echo "\nPHP files have been removed after conversion.\n";
} else {
	echo "\nPHP files are still present. Use --remove-php to remove them.\n";
}

exit($failedFiles > 0 ? 1 : 0);

