#!/usr/bin/env php
<?php
/**
 * Add missing property declarations based on PHPStan analysis
 * 
 * Usage: 
 *   php add-missing-properties.php --dry-run <file-to-analyze>  # Preview changes
 *   php add-missing-properties.php <file-to-analyze>            # Apply changes
 * 
 * Examples:
 *   php add-missing-properties.php --dry-run src/events/VTWSEntityType.php
 *   php add-missing-properties.php src/events/VTWSEntityType.php
 */

$dryRun = false;
$file = null;

// Parse arguments
foreach ($argv as $i => $arg) {
    if ($i === 0) continue; // Skip script name
    
    if ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (!$file) {
        $file = $arg;
    }
}

if (!$file) {
    echo "Usage: php add-missing-properties.php [--dry-run] <file-to-analyze>\n";
    echo "  --dry-run  Preview changes without modifying files\n\n";
    echo "Examples:\n";
    echo "  php add-missing-properties.php --dry-run src/events/VTWSEntityType.php\n";
    echo "  php add-missing-properties.php src/events/VTWSEntityType.php\n";
    exit(1);
}

if (!file_exists($file)) {
    echo "Error: File not found: $file\n";
    exit(1);
}

echo ($dryRun ? "[DRY RUN MODE] " : "") . "Analyzing $file...\n\n";

// Run PHPStan on the file
$output = shell_exec("vendor/bin/phpstan analyse $file --level=5 --error-format=json 2>&1");

// Extract JSON from output (might have text before it)
if (preg_match('/\{.*"files".*\}/s', $output, $matches)) {
    $json = $matches[0];
    $data = json_decode($json, true);
} else {
    echo "Error: Could not parse PHPStan output\n";
    exit(1);
}

if (!$data || !isset($data['files'])) {
    echo "Error: Invalid PHPStan data structure\n";
    exit(1);
}

$properties = [];

foreach ($data['files'] as $filename => $fileData) {
    foreach ($fileData['messages'] as $message) {
        // Look for "Access to an undefined property" errors
        if (strpos($message['message'], 'Access to an undefined property') !== false) {
            // Extract class and property name
            // Format: "Access to an undefined property App\Events\VTWSEntityType::$entityTypeName."
            if (preg_match('/Access to an undefined property ([^:]+)::\$(\w+)/', $message['message'], $matches)) {
                $className = $matches[1];
                $propertyName = $matches[2];
                
                if (!isset($properties[$className])) {
                    $properties[$className] = [];
                }
                
                if (!in_array($propertyName, $properties[$className])) {
                    $properties[$className][] = $propertyName;
                }
            }
        }
    }
}

if (empty($properties)) {
    echo "✅ No undefined properties found!\n";
    exit(0);
}

// Read the source file
$sourceCode = file_get_contents($file);
$lines = explode("\n", $sourceCode);

// Process each class
foreach ($properties as $className => $props) {
    $shortClassName = substr($className, strrpos($className, '\\') + 1);
    
    echo "Found " . count($props) . " undefined properties in class: $shortClassName\n";
    
    sort($props);
    
    // Generate property declarations
    $propertyDeclarations = [];
    $visibility = guessPropertyVisibility($shortClassName);
    foreach ($props as $prop) {
        $type = guessPropertyType($prop);
        $propertyDeclarations[] = "    /** @var $type */";
        $propertyDeclarations[] = "    $visibility \$$prop;";
    }
    
    echo "Properties to add:\n";
    foreach ($propertyDeclarations as $line) {
        echo "  " . $line . "\n";
    }
    echo "\n";
    
    if (!$dryRun) {
        // Find the class and insert properties
        $modified = insertProperties($lines, $shortClassName, $propertyDeclarations);
        
        if ($modified) {
            $newContent = implode("\n", $lines);
            file_put_contents($file, $newContent);
            echo "✅ Properties added to $file\n\n";
        } else {
            echo "⚠️  Could not find insertion point for class $shortClassName\n\n";
        }
    } else {
        echo "[DRY RUN] Would add properties to class $shortClassName\n\n";
    }
}

if ($dryRun) {
    echo "=== DRY RUN COMPLETE ===\n";
    echo "Run without --dry-run to apply changes\n";
} else {
    echo "=== COMPLETE ===\n";
    echo "Properties have been added. Run PHPStan again to verify:\n";
    echo "  vendor/bin/phpstan analyse $file --level=5\n";
}

/**
 * Insert property declarations into the class
 */
function insertProperties(&$lines, $className, $propertyDeclarations) {
    $classLineIndex = -1;
    $insertLineIndex = -1;
    
    // Find the class declaration (including abstract classes)
    foreach ($lines as $i => $line) {
        if (preg_match('/^(abstract\s+)?class\s+' . preg_quote($className, '/') . '\b/', $line)) {
            $classLineIndex = $i;
            break;
        }
    }
    
    if ($classLineIndex === -1) {
        return false;
    }
    
    // Find the opening brace of the class
    $braceFound = false;
    for ($i = $classLineIndex; $i < count($lines); $i++) {
        if (strpos($lines[$i], '{') !== false) {
            $insertLineIndex = $i + 1;
            $braceFound = true;
            break;
        }
    }
    
    if (!$braceFound) {
        return false;
    }
    
    // Check if there are already properties or if we should add after existing ones
    // Look for existing property declarations
    $hasExistingProperties = false;
    $lastPropertyLine = $insertLineIndex;
    
    for ($i = $insertLineIndex; $i < count($lines); $i++) {
        $trimmed = trim($lines[$i]);
        
        // If we hit a function/method, stop looking
        if (preg_match('/^\s*(public|protected|private|static)?\s*(function|static\s+function)/', $lines[$i])) {
            break;
        }
        
        // If we find a property declaration, update our insertion point
        if (preg_match('/^\s*(public|protected|private)\s+\$/', $lines[$i])) {
            $hasExistingProperties = true;
            $lastPropertyLine = $i;
        }
    }
    
    // If there are existing properties, insert after them
    if ($hasExistingProperties) {
        $insertLineIndex = $lastPropertyLine + 1;
    }
    
    // Add a blank line before properties if needed
    if ($insertLineIndex > 0 && trim($lines[$insertLineIndex - 1]) !== '') {
        array_splice($lines, $insertLineIndex, 0, ['']);
        $insertLineIndex++;
    }
    
    // Insert the property declarations
    array_splice($lines, $insertLineIndex, 0, $propertyDeclarations);
    
    // Add a blank line after properties
    array_splice($lines, $insertLineIndex + count($propertyDeclarations), 0, ['']);
    
    return true;
}

/**
 * Guess property visibility based on class name
 */
function guessPropertyVisibility($className) {
    // Simple value object classes typically use public properties
    $valueObjectClasses = ['VTWSFieldType', 'VTFieldType', 'SqlResultIteratorRow'];
    
    if (in_array($className, $valueObjectClasses)) {
        return 'public';
    }
    
    // Default to protected for encapsulation
    return 'protected';
}

/**
 * Guess the property type based on naming conventions
 */
function guessPropertyType($propertyName) {
    // Common patterns
    if (strpos($propertyName, 'is') === 0 || strpos($propertyName, 'has') === 0) {
        return 'bool';
    }
    if ($propertyName === 'tabId' || $propertyName === 'pos') {
        return 'int|null';
    }
    if ($propertyName === 'id') {
        return 'int|string';
    }
    if (strpos($propertyName, 'Id') !== false) {
        return 'int|string|null';
    }
    if (in_array($propertyName, ['adb', 'db'])) {
        return '\App\Database\PearDatabase|null';
    }
    if ($propertyName === 'result') {
        return 'mixed'; // Database query result
    }
    if ($propertyName === 'rows') {
        return 'int|array|null';
    }
    if ($propertyName === 'module' && !preg_match('/Name$/', $propertyName)) {
        return 'object|null'; // Module model instance
    }
    if ($propertyName === 'data') {
        return 'array';
    }
    if (preg_match('/Data$/', $propertyName) || preg_match('/RawData/', $propertyName)) {
        return 'array';
    }
    if (in_array($propertyName, ['entityTypeName', 'moduleName'])) {
        return 'string';
    }
    if (in_array($propertyName, ['description', 'fieldNames', 'fieldLabels', 'fieldTypes'])) {
        return 'array|null';
    }
    if ($propertyName === 'focus') {
        return '\CRMEntity|null';
    }
    if ($propertyName === 'entityId') {
        return 'int|string';
    }
    if ($propertyName === 'parent') {
        return 'self|null';
    }
    if ($propertyName === 'children') {
        return 'array';
    }
    if ($propertyName === 'profiles' || $propertyName === 'cache') {
        return 'array';
    }
    if ($propertyName === 'user') {
        return 'mixed'; // Could be Users_Record_Model or other user object
    }
    if (preg_match('/Models?$/', $propertyName) || preg_match('/FieldModels/', $propertyName) || preg_match('/Instances$/', $propertyName)) {
        return 'array|null';
    }
    if (preg_match('/Fields$/', $propertyName) && $propertyName !== 'relatedFields') {
        return 'array|null';
    }
    if ($propertyName === 'reportRun') {
        return 'object|null'; // ReportRun instance
    }
    if (strpos($propertyName, 'type') !== false || strpos($propertyName, 'Type') !== false) {
        return 'string|null';
    }
    if (strpos($propertyName, 'format') !== false) {
        return 'string|null';
    }
    if ($propertyName === 'relatedTo') {
        return 'array|string|null';
    }
    if ($propertyName === 'values') {
        return 'array|null';
    }
    if (strpos($propertyName, 'Name') !== false || strpos($propertyName, 'name') !== false) {
        return 'string|null';
    }
    
    return 'mixed';
}

