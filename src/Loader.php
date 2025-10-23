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

namespace App;

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
		string $moduleName = 'Vtiger',
		bool $throwException = true
	) {
		// Store original module name for legacy fallback
		$originalModuleName = $moduleName;
		
		// Handle Settings:SubModule pattern → Settings\SubModule
		if (strpos($moduleName, ':') !== false) {
			$moduleName = str_replace(':', '\\', $moduleName);
		}

		// Handle special case: view=List maps to ListView class (PHP reserved keyword workaround)
		if ($componentType === 'View' && $componentName === 'List') {
			$componentName = 'ListView';
		}

		// Convert type to plural directory name: View → Views, Action → Actions, Model → Models
		$typeDir = ucfirst(strtolower($componentType)) . 's';

		// Build fully qualified PSR-4 class name
		$className = "App\\Modules\\{$moduleName}\\{$typeDir}\\{$componentName}";

		// Check if module-specific class exists
		if (class_exists($className)) {
			return $className;
		}

		// For Settings modules, try Settings\Vtiger fallback
		if (strpos($originalModuleName, 'Settings:') === 0) {
			$settingsVtigerFallback = "App\\Modules\\Settings\\Vtiger\\{$typeDir}\\{$componentName}";
			if (class_exists($settingsVtigerFallback)) {
				return $settingsVtigerFallback;
			}
			
			// Try base submodule without Settings prefix
			$parts = explode(':', $originalModuleName);
			if (count($parts) > 1) {
				$subModule = $parts[1];
				$subModuleFallback = "App\\Modules\\{$subModule}\\{$typeDir}\\{$componentName}";
				if (class_exists($subModuleFallback)) {
					return $subModuleFallback;
				}
			}
		}

		// Fallback to Vtiger base class (inheritance pattern)
		$fallbackClass = "App\\Modules\\Vtiger\\{$typeDir}\\{$componentName}";
		if (class_exists($fallbackClass)) {
			return $fallbackClass;
		}

		// Legacy fallback: check if old-style class name exists
		// Convert Settings:PDF → Settings_PDF_ComponentName_Type
		$legacyClassName = str_replace([':', '\\'], '_', $originalModuleName) . '_' . $componentName . '_' . $componentType;
		if (class_exists($legacyClassName)) {
			return $legacyClassName;
		}

		// Component not found
		if ($throwException) {
			\App\Log::error("Loader::getComponentClassName($componentType, $componentName, $originalModuleName): Handler not found");
			throw new \Exception\AppException('LBL_HANDLER_NOT_FOUND');
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
		string $moduleName = 'Vtiger'
	): string {
		// Handle Settings:SubModule
		$modulePath = str_replace(':', DIRECTORY_SEPARATOR, $moduleName);
		
		// Pluralize type
		$typeDir = ucfirst(strtolower($componentType)) . 's';
		
		return "src/Modules/{$modulePath}/{$typeDir}/{$componentName}.php";
	}
}

