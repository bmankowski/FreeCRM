<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * ImportManager module vtlib handler.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager;

class ImportManager
{
	public $default_order_by = '';
	public $default_sort_order = 'DESC';

	/**
	 * Entry point for vtlib events.
	 */
	public function vtlib_handler(string $moduleName, string $eventType): void
	{
		if ($eventType === 'module.postinstall') {
			\App\Db\Db::getInstance()->createCommand()
				->update('vtiger_tab', ['customized' => 0], ['name' => $moduleName])
				->execute();
		}
	}
}

