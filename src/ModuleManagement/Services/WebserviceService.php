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

namespace App\ModuleManagement\Services;

/**
 * WebserviceService class.
 * 
 * Service for webservice operations.
 */
class WebserviceService
{
	/**
	 * Initialize webservice for a module.
	 * 	 * @param int $moduleId Module ID
	 * @param string $moduleName Module name
	 * @param bool $isEntityType Whether module is entity type
	 * @return void
	 */
	public function initialize(int $moduleId, string $moduleName, bool $isEntityType): void
	{
		if ($isEntityType) {
			if (function_exists('vtws_addDefaultModuleTypeEntity')) {
				vtws_addDefaultModuleTypeEntity($moduleName);
			}
		}
	}

	/**
	 * Uninitialize webservice for a module.
	 * 	 * @param string $moduleName Module name
	 * @param bool $isEntityType Whether module is entity type
	 * @return void
	 */
	public function uninitialize(string $moduleName, bool $isEntityType): void
	{
		if ($isEntityType) {
			if (function_exists('vtws_deleteWebserviceEntity')) {
				vtws_deleteWebserviceEntity($moduleName);
			}
		}
	}
}





