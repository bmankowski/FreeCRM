<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * ImportManager module vtlib handler.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager;

class ImportManager extends \App\Core\CRMEntity
{
	public $table_name = 'vtiger_import_batches';
	public $table_index = 'id';
	public $tab_name = ['vtiger_import_batches'];
	public $tab_name_index = ['vtiger_import_batches' => 'id'];
	public $list_fields = [];
	public $list_fields_name = [];
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
