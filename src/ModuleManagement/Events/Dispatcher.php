<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\ModuleManagement\Events;

use App\ModuleManagement\ServiceLocator;

/**
 * Dispatcher class.
 * 
 * Event dispatcher maintaining vtlib_handler compatibility.
 */
class Dispatcher
{
	/**
	 * Fire an event for a module (if vtlib_handler is defined).
	 * 	 * This maintains compatibility with the existing vtlib_handler pattern
	 * used throughout FreeCRM modules.
	 * 	 * @param string $moduleName Module name
	 * @param string $eventType Event type (e.g., 'module.postinstall')
	 * @return bool True if event was handled successfully, false otherwise
	 */
	public function fire(string $moduleName, string $eventType): bool
	{
		$return = true;
		$instance = $this->getClassInstance((string) $moduleName);
		
		if ($instance) {
			if (method_exists($instance, 'vtlib_handler')) {
				$fire = $instance->vtlib_handler((string) $moduleName, (string) $eventType);
				if ($fire !== null && $fire !== true) {
					$return = false;
				}
			}
		}
		
		return $return;
	}

	/**
	 * Get instance of the module class.
	 * 	 * @param string $moduleName Module name
	 * @return object|false Module instance or false if not found
	 */
	private function getClassInstance(string $moduleName)
	{
		// Calendar module uses Activity class
		if ($moduleName == 'Calendar') {
			$moduleName = 'Activity';
		}

		$instance = false;
		$filepath = ROOT_DIRECTORY . "/modules/$moduleName/$moduleName.php";
		
		$fileService = ServiceLocator::getFileService();
		if ($fileService->checkFileAccessForInclusion($filepath, false)) {
			include_once($filepath);
			if (class_exists($moduleName)) {
				$instance = new $moduleName();
			}
		}
		
		return $instance;
	}
}

