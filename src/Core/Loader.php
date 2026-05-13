<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com, App Modernization
 * ********************************************************************************** */

namespace App\Core;

/**
 * Modern PSR-4 Module Loader
 * 
 * Unified loader combining PSR-4 component loading and legacy asset resolution
 * Replaces legacy Vtiger_Loader
 * 
 * @package App
 */
class Loader
{
	/** @var array Cache for included files */
	protected static $includeCache = [];
	
	/** @var array Cache for included paths */
	protected static $includePathCache = [];
	
	/** @var array Cache for resolved component class names (PERFORMANCE) */
	protected static $componentClassCache = [];
	
	/** @var array Directories to search for legacy modules */
	protected static $loaderDirs = [
		'src.Modules.',        // PSR-4 migrated modules (new location, first priority)
		'custom.modules.',     // Custom module overrides
		'old_modules.',        // Settings subsystem only (unmigrated)
		'admin.modules.',      // Admin modules
	];
	/**
	 * Get PSR-4 component class name
	 * 
	 * Resolves module components (Views, Actions, Models, etc.) to their
	 * fully qualified class names following PSR-4 standard.
	 * 
	 * @param string $componentType Type of component (View, Action, Model, etc.)
	 * @param string $componentName Name of the component (Detail, Save, Record, etc.)
	 * @param string $moduleName Module name (can include Settings:SubModule pattern)
	 * @param bool $throwException Whether to throw exception if not found (default: true)
	 * @return string|false Fully qualified class name or false if not found and $throwException is false
	 * @throws \Exception When component class is not found and $throwException is true
	 */
	public static function getComponentClassName(
		string $componentType,
		string $componentName,
		string $moduleName = 'Base',
		bool $throwException = true
	) {
		// PERFORMANCE: Check cache first
		$cacheKey = "{$moduleName}_{$componentType}_{$componentName}";
		if (isset(self::$componentClassCache[$cacheKey])) {
			return self::$componentClassCache[$cacheKey];
		}
		
		// Store original module name for legacy fallback
		$originalModuleName = $moduleName;
		
		// Handle Settings:SubModule pattern → Settings\SubModule
		if (strpos($moduleName, ':') !== false) {
			$moduleName = str_replace(':', '\\', $moduleName);
		}

	// Handle special case: view=ListView maps to ListView class (PHP reserved keyword workaround)
	if ($componentType === 'View' && $componentName === 'List') {
		$componentName = 'ListView';
	}

	// Convert type to plural directory name: View → Views, Action → Actions, Model → Models
	// Special case for UIType → UiTypes (preserve camelCase)
	if ($componentType === 'UIType') {
		$typeDir = 'UiTypes';
	} elseif ($componentType === 'InventoryField') {
		$typeDir = 'InventoryFields';
	} else {
		$typeDir = ucfirst(strtolower($componentType)) . 's';
	}

		// Build fully qualified PSR-4 class name and file path
		// Try exact case first, then try capitalized first letter (common convention)
		$className = "App\\Modules\\{$moduleName}\\{$typeDir}\\{$componentName}";
		$filePath = "src/Modules/" . str_replace('\\', '/', $moduleName) . "/{$typeDir}/{$componentName}.php";
		
		// PERFORMANCE: Check file exists before class_exists to avoid slow autoloader attempts
		if (file_exists(ROOT_DIRECTORY . '/' . $filePath) && class_exists($className)) {
			self::$componentClassCache[$cacheKey] = $className;
			return $className;
		}
		
		// Prepare capitalized version for case-insensitive matching (common PHP class naming convention)
		$capitalizedComponentName = ucfirst($componentName);
		if ($capitalizedComponentName !== $componentName) {
			$capitalizedClassName = "App\\Modules\\{$moduleName}\\{$typeDir}\\{$capitalizedComponentName}";
			$capitalizedFilePath = "src/Modules/" . str_replace('\\', '/', $moduleName) . "/{$typeDir}/{$capitalizedComponentName}.php";
			
			if (file_exists(ROOT_DIRECTORY . '/' . $capitalizedFilePath) && class_exists($capitalizedClassName)) {
				self::$componentClassCache[$cacheKey] = $capitalizedClassName;
				return $capitalizedClassName;
			}
		}

		// For Settings modules, try Settings\Base fallback
		if (strpos($originalModuleName, 'Settings:') === 0) {
			$settingsVtigerFallback = "App\\Modules\\Settings\\Base\\{$typeDir}\\{$componentName}";
			$settingsFilePath = "src/Modules/Settings/Base/{$typeDir}/{$componentName}.php";
			
			if (file_exists(ROOT_DIRECTORY . '/' . $settingsFilePath) && class_exists($settingsVtigerFallback)) {
				self::$componentClassCache[$cacheKey] = $settingsVtigerFallback;
				return $settingsVtigerFallback;
			}
			
			// Try capitalized version for Settings\Base fallback
			if ($capitalizedComponentName !== $componentName) {
				$settingsCapitalizedFallback = "App\\Modules\\Settings\\Base\\{$typeDir}\\{$capitalizedComponentName}";
				$settingsCapitalizedFilePath = "src/Modules/Settings/Base/{$typeDir}/{$capitalizedComponentName}.php";
				
				if (file_exists(ROOT_DIRECTORY . '/' . $settingsCapitalizedFilePath) && class_exists($settingsCapitalizedFallback)) {
					self::$componentClassCache[$cacheKey] = $settingsCapitalizedFallback;
					return $settingsCapitalizedFallback;
				}
			}
			
			// Try base submodule without Settings prefix
			$parts = explode(':', $originalModuleName);
			if (count($parts) > 1) {
				$subModule = $parts[1];
				$subModuleFallback = "App\\Modules\\{$subModule}\\{$typeDir}\\{$componentName}";
				$subModuleFilePath = "src/Modules/{$subModule}/{$typeDir}/{$componentName}.php";
				
				if (file_exists(ROOT_DIRECTORY . '/' . $subModuleFilePath) && class_exists($subModuleFallback)) {
					self::$componentClassCache[$cacheKey] = $subModuleFallback;
					return $subModuleFallback;
				}
				
				// Try capitalized version for submodule fallback
				if ($capitalizedComponentName !== $componentName) {
					$subModuleCapitalizedFallback = "App\\Modules\\{$subModule}\\{$typeDir}\\{$capitalizedComponentName}";
					$subModuleCapitalizedFilePath = "src/Modules/{$subModule}/{$typeDir}/{$capitalizedComponentName}.php";
					
					if (file_exists(ROOT_DIRECTORY . '/' . $subModuleCapitalizedFilePath) && class_exists($subModuleCapitalizedFallback)) {
						self::$componentClassCache[$cacheKey] = $subModuleCapitalizedFallback;
						return $subModuleCapitalizedFallback;
					}
				}
			}
		}

		// Fallback to Vtiger base class (inheritance pattern)
		$fallbackClass = "App\\Modules\\Base\\{$typeDir}\\{$componentName}";
		$vtigerFilePath = "src/Modules/Base/{$typeDir}/{$componentName}.php";
		
		if (file_exists(ROOT_DIRECTORY . '/' . $vtigerFilePath) && class_exists($fallbackClass)) {
			self::$componentClassCache[$cacheKey] = $fallbackClass;
			return $fallbackClass;
		}
		
		// Try capitalized version for base fallback
		if ($capitalizedComponentName !== $componentName) {
			$fallbackCapitalizedClass = "App\\Modules\\Base\\{$typeDir}\\{$capitalizedComponentName}";
			$vtigerCapitalizedFilePath = "src/Modules/Base/{$typeDir}/{$capitalizedComponentName}.php";
			
			if (file_exists(ROOT_DIRECTORY . '/' . $vtigerCapitalizedFilePath) && class_exists($fallbackCapitalizedClass)) {
				self::$componentClassCache[$cacheKey] = $fallbackCapitalizedClass;
				return $fallbackCapitalizedClass;
			}
		}

		// Legacy fallback: check if old-style class name exists
		// Convert Settings:Template → Settings_Template_ComponentName_Type
		$legacyClassName = str_replace([':', '\\'], '_', $originalModuleName) . '_' . $componentName . '_' . $componentType;
		if (class_exists($legacyClassName)) {
			self::$componentClassCache[$cacheKey] = $legacyClassName;
			return $legacyClassName;
		}

		// Component not found - log all attempted paths for debugging
		if ($throwException) {
			$attemptedPaths = [
				"App\\Modules\\{$moduleName}\\{$typeDir}\\{$componentName}",
				"App\\Modules\\{$moduleName}\\{$typeDir}\\{$capitalizedComponentName}",
			];
			if (strpos($originalModuleName, 'Settings:') === 0) {
				$attemptedPaths[] = "App\\Modules\\Settings\\Base\\{$typeDir}\\{$componentName}";
				$attemptedPaths[] = "App\\Modules\\Settings\\Base\\{$typeDir}\\{$capitalizedComponentName}";
				$parts = explode(':', $originalModuleName);
				if (count($parts) > 1) {
					$subModule = $parts[1];
					$attemptedPaths[] = "App\\Modules\\{$subModule}\\{$typeDir}\\{$componentName}";
					$attemptedPaths[] = "App\\Modules\\{$subModule}\\{$typeDir}\\{$capitalizedComponentName}";
				}
			}
			$attemptedPaths[] = "App\\Modules\\Base\\{$typeDir}\\{$componentName}";
			$attemptedPaths[] = "App\\Modules\\Base\\{$typeDir}\\{$capitalizedComponentName}";
			
			\App\Log\Log::error(
				"Loader::getComponentClassName($componentType, $componentName, $originalModuleName): Handler not found. " .
				"Attempted paths: " . implode(', ', array_unique($attemptedPaths))
			);
			throw new \App\Exceptions\AppException('LBL_HANDLER_NOT_FOUND');
		}
		return false;
	}

	/**
	 * Resolve component file path (for validation/debugging)
	 * 
	 * @param string $componentType
	 * @param string $componentName
	 * @param string $moduleName
	 * @return string File path relative to ROOT_DIRECTORY
	 */
	public static function getComponentFilePath(
		string $componentType,
		string $componentName,
		string $moduleName = 'Base'
	): string {
	// Handle Settings:SubModule
	$modulePath = str_replace(':', DIRECTORY_SEPARATOR, $moduleName);
	
	// Pluralize type
	// Special case for UIType → UiTypes (preserve camelCase)
	if ($componentType === 'UIType') {
		$typeDir = 'UiTypes';
	} else {
		$typeDir = ucfirst(strtolower($componentType)) . 's';
	}
		
		return "src/Modules/{$modulePath}/{$typeDir}/{$componentName}.php";
	}

	/**
	 * Resolve qualified name to absolute file path
	 * 
	 * Handles asset loading (JS, CSS, LESS) and PHP files with special prefixes:
	 * - Prefix ~ means literal path from root
	 * - No prefix means convert dots to directory separators
	 * - Checks public/ directory first for assets
	 * - Language files (languages.*) default to JSON format
	 * 
	 * @param string $qualifiedName Qualified resource name (e.g., 'libraries.jquery.jquery' or '~layouts/basic/style.css')
	 * @param string $fileExtension File extension (php, js, css, less, json)
	 * @return string Absolute file path
	 */
	public static function resolveNameToPath($qualifiedName, $fileExtension = 'php')
	{
		$allowedExtensions = ['php', 'js', 'css', 'less', 'json', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
		
		// Language files default to JSON format
		if ($fileExtension === 'php' && strpos($qualifiedName, 'languages.') === 0) {
			$fileExtension = 'json';
		}
		
		// Handle ~ prefix (literal path from root)
		if (strpos($qualifiedName, '~') === 0) {
			$file = str_replace('~', '', $qualifiedName);
			// Try to detect extension from file path if not provided explicitly
			if ($fileExtension === 'php') {
				$pathExtension = pathinfo($file, PATHINFO_EXTENSION);
				if ($pathExtension && in_array($pathExtension, $allowedExtensions)) {
					$fileExtension = $pathExtension;
				}
			}
		} else {
			$file = str_replace('.', DIRECTORY_SEPARATOR, $qualifiedName) . '.' . $fileExtension;
		}
		
		// Check public/ for web assets (JS/CSS/images/fonts) first
		if (in_array($fileExtension, ['js', 'css', 'less', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'])) {
			$publicFile = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $file;
			if (file_exists($publicFile)) {
				return $publicFile;
			}
		}
		
		// Fallback to root directory
		$file = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . $file;
		return $file;
	}

	/**
	 * Include a PHP file once with caching
	 * 
	 * @param string $qualifiedName Qualified file name
	 * @param bool $suppressWarning Whether to suppress inclusion warnings
	 * @return bool True if included successfully, false otherwise
	 */
	public static function includeOnce($qualifiedName, $suppressWarning = false)
	{
		if (isset(self::$includeCache[$qualifiedName])) {
			return true;
		}

		$file = self::resolveNameToPath($qualifiedName);

		if (!file_exists($file)) {
			return false;
		}

		// Check file inclusion before including it
		\vtlib\Deprecated::checkFileAccessForInclusion($file);

		$status = -1;
		if ($suppressWarning) {
			$status = @include_once $file;
		} else {
			$status = include_once $file;
		}

		$success = ($status === 0) ? false : true;

		if ($success) {
			self::$includeCache[$qualifiedName] = $file;
		}

		return $success;
	}

	/**
	 * Add path to PHP include path
	 * 
	 * @param string $qualifiedName Qualified path name
	 * @return bool Always returns true
	 */
	public static function includePath($qualifiedName)
	{
		// Already included?
		if (isset(self::$includePathCache[$qualifiedName])) {
			return true;
		}

		$path = realpath(self::resolveNameToPath($qualifiedName));
		self::$includePathCache[$qualifiedName] = $path;

		set_include_path($path . PATH_SEPARATOR . get_include_path());
		return true;
	}

	/**
	 * Auto-load legacy class files
	 * 
	 * Handles legacy Module_Component_Type pattern (e.g., Settings_Base_Module_Model)
	 * Searches in multiple directories with fallback support
	 * 
	 * @param string $className Legacy class name
	 * @return bool True if loaded successfully, false otherwise
	 */
	public static function autoLoad($className)
	{
		$parts = explode('_', $className);
		$noOfParts = count($parts);
		if ($noOfParts > 2) {
			foreach (self::$loaderDirs as $filePath) {
				// Append modules and sub modules names to the path
				for ($i = 0; $i < ($noOfParts - 2); ++$i) {
					$filePath .= $parts[$i] . '.';
				}

				$fileName = $parts[$noOfParts - 2];
				$fileComponentName = strtolower($parts[$noOfParts - 1]) . 's';
				
				// For PSR-4 locations (src.Modules.*), use capitalized component names (Views, Actions, Models)
				if (strpos($filePath, 'src.Modules.') === 0) {
					$fileComponentNameCapitalized = ucfirst($fileComponentName);
					$filePath .= $fileComponentNameCapitalized . '.' . $fileName;
				} else {
					$filePath .= $fileComponentName . '.' . $fileName;
				}

				if (file_exists(self::resolveNameToPath($filePath))) {
					return self::includeOnce($filePath);
				}
			}
		}
		return false;
	}

	/**
	 * Register the legacy autoloader
	 * 
	 * Should be called during application bootstrap to support
	 * legacy Module_Component_Type class naming
	 */
	public static function register()
	{
		spl_autoload_register([__CLASS__, 'autoLoad']);
	}
}

