<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com, FreeCRM Modernization
 * ********************************************************************************** */

namespace FreeCRM;

/**
 * Modern PSR-4 Module Loader
 * 
 * Replaces legacy Vtiger_Loader for PSR-4 namespaced modules in src/Modules/
 * 
 * @package FreeCRM
 */
class Loader
{
	/**
	 * Get PSR-4 component class name
	 * 
	 * Resolves module components (Views, Actions, Models, etc.) to their
	 * fully qualified class names following PSR-4 standard.
	 * 
	 * @param string $componentType Type of component (View, Action, Model, etc.)
	 * @param string $componentName Name of the component (Detail, Save, Record, etc.)
	 * @param string $moduleName Module name (can include Settings:SubModule pattern)
	 * @return string Fully qualified class name
	 * @throws \Exception When component class is not found
	 */
	public static function getComponentClassName(
		string $componentType,
		string $componentName,
		string $moduleName = 'Vtiger'
	): string {
		// Handle Settings:SubModule pattern → Settings\SubModule
		if (strpos($moduleName, ':') !== false) {
			$moduleName = str_replace(':', '\\', $moduleName);
		}

		// Handle special case: view=List maps to ListView class (reserved keyword workaround)
		if ($componentType === 'View' && $componentName === 'List') {
			$componentName = 'ListView';
		}

		// Convert type to plural directory name: View → Views, Action → Actions, Model → Models
		$typeDir = ucfirst(strtolower($componentType)) . 's';

		// Build fully qualified PSR-4 class name
		$className = "FreeCRM\Modules\\{$moduleName}\\{$typeDir}\\{$componentName}";

		// Check if module-specific class exists
		if (class_exists($className)) {
			return $className;
		}

		// Fallback to Vtiger base class (inheritance pattern)
		$fallbackClass = "FreeCRM\Modules\\Vtiger\\{$typeDir}\\{$componentName}";
		if (class_exists($fallbackClass)) {
			return $fallbackClass;
		}

		// Component not found
		throw new \Exception("Module component not found: {$className}");
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

