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
 * MenuService class.
 * 
 * Service for menu operations.
 */
class MenuService
{
	/** @var \App\Db Database instance */
	private $db;

	/**
	 * Constructor.
	 * 
	 * @param \App\Db\Db $db
	 */
	public function __construct(\App\Db\Db $db)
	{
		$this->db = $db;
	}

	/**
	 * Delete menu items for a module.
	 * 
	 * @param int $moduleId Module ID
	 * @return void
	 */
	public function deleteForModule(int $moduleId): void
	{
		$id = (new \App\Db\Query())
			->select('id')
			->from('yetiforce_menu')
			->where(['module' => $moduleId])
			->scalar();

		if ($id) {
			$this->db->createCommand()
				->delete('yetiforce_menu', ['module' => $moduleId])
				->execute();

			$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
			$menuRecordModel->refreshMenuFiles();
		}
	}
}



