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
 * LanguageService class.
 * 
 * Service for language file operations.
 */
class LanguageService
{
	/**
	 * Delete language files for a module.
	 * Deletes JSON language files (YetiForce compatible format).
	 * 
	 * @param string $moduleName Module name
	 * @return void
	 */
	public function deleteForModule(string $moduleName): void
	{
		$query = (new \App\Db\Query())
			->select(['prefix'])
			->from('vtiger_language');

		foreach ($query->column() as $lang) {
			// Delete JSON language files
			$langFilePath = ROOT_DIRECTORY . "/languages/$lang/{$moduleName}.json";
			if (file_exists($langFilePath)) {
				unlink($langFilePath);
			}

			$langFilePath = ROOT_DIRECTORY . "/languages/$lang/Settings/{$moduleName}.json";
			if (file_exists($langFilePath)) {
				unlink($langFilePath);
			}
		}
	}
}



