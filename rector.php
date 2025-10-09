<?php

// Enable strict type checking for PHP 7+
declare(strict_types=1);

// Import Rector configuration classes
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use App\Rector\ReplaceDirWithRootDirectoryRector;
use App\Rector\AddNamespaceBasedOnLocationRector;

return RectorConfig::configure()
    // Define which directories to process
    ->withPaths([
        __DIR__ . '/include',    // Core application code (WebUI, ConfigUtils, etc.)
        __DIR__ . '/modules',    // Module-specific code (Users, Accounts, etc.)
    ])
    
    // // Ensure project autoload and bootstrap files are loaded for Rector run
    // ->withBootstrapFiles([
    //     __DIR__ . '/vendor/autoload.php',
    // ])
    
    // Define rule sets to apply
    ->withSets([
        // PHP version compatibility - modernizes code to PHP 7.4+ features
        // Examples: array() → [], null coalescing ??, typed properties, arrow functions
        LevelSetList::UP_TO_PHP_84,
        
        // Code quality improvements
        // Examples: remove unused variables, simplify boolean logic, optimize conditionals
        SetList::CODE_QUALITY,
        
        // Coding style and formatting consistency
        // Examples: consistent indentation, spacing, import organization, method ordering
        SetList::CODING_STYLE,
        
        // Remove unused/dead code
        // Examples: unused imports, unreachable code, unused parameters, empty classes
        SetList::DEAD_CODE,
        
        // Simplify control flow with early returns
        // Examples: if-else → early return pattern for better readability
        SetList::EARLY_RETURN,
        
        // Add type hints where safe
        // Examples: return types, parameter types, property types, union types (PHP 8+)
        SetList::TYPE_DECLARATION,

        SetList::NAMING

    ])
    
    // Custom rules for FreeCRM-specific transformations
    ->withRules([
        // Replace __DIR__ with ROOT_DIRECTORY for better path resolution
        ReplaceDirWithRootDirectoryRector::class,
    ])
    
    // Add namespace declarations based on file location
    ->withConfiguredRule(AddNamespaceBasedOnLocationRector::class, [
        'mappings' => [
            // Map directories to their PSR-4 namespace prefixes
            // Order matters: most specific paths first
            'include/exceptions/' => 'Exception\\',
            'include/' => 'includes\\',
            'src/' => 'FreeCRM\\',
            'vtlib/Vtiger/' => 'vtlib\\',
            'api/webservice/' => 'Api\\',
        ]
    ])
    
    // Define what to skip/exclude
    ->withSkip([
        // Skip vendor directory - third-party Composer packages
        __DIR__ . '/vendor',
        
        // Skip specific legacy files that might break if modified
        __DIR__ . '/include/LegacyAliases.php',
        
        // Skip directories with legacy/third-party code
        __DIR__ . '/libraries',  // Third-party libraries (HTTP_Session, etc.)
        __DIR__ . '/cache',      // Cache files (temporary, regenerated)
        __DIR__ . '/storage',    // Application storage files
        
        // Skip specific rule that conflicts with FreeCRM architecture
        // This rule would change require_once paths from ROOT_DIRECTORY to __DIR__
        // FreeCRM uses ROOT_DIRECTORY constant for consistent path resolution
        // \Rector\CodeQuality\Rector\Include_\AbsolutizeRequireAndIncludePathRector::class,
    ]);
