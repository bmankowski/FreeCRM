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

use App\ModuleManagement\Models;
use App\ModuleManagement\Events;

/**
 * ModuleService class.
 * 
 * Service for managing module lifecycle operations.
 */
class ModuleService
{
	/** @var \App\Db Database instance */
	private $db;
	
	/** @var Events\Dispatcher Event dispatcher */
	private $eventDispatcher;

	/**
	 * Constructor.
	 * 
	 * @param \App\Db\Db $db
	 * @param Events\Dispatcher $eventDispatcher
	 */
	public function __construct(\App\Db\Db $db, Events\Dispatcher $eventDispatcher)
	{
		$this->db = $db;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * Create a new module.
	 * 
	 * @param Models\Module $module
	 * @return int Module ID
	 * @throws \Exception
	 */
	public function create(Models\Module $module): int
	{
		$transaction = $this->db->beginTransaction();
		try {
			$moduleId = $this->db->getUniqueID('vtiger_tab', 'tabid', false);
			$tabsequence = $module->getTabsequence();
			if (!$tabsequence) {
				$tabsequence = $this->db->getUniqueID('vtiger_tab', 'tabsequence', false);
			}
			$label = $module->getLabel() ?: $module->getName();
			$customized = 1; // Custom module

			$this->db->createCommand()->insert('vtiger_tab', [
				'tabid' => $moduleId,
				'name' => $module->getName(),
				'presence' => $module->getPresence(),
				'tabsequence' => $tabsequence,
				'tablabel' => $label,
				'modifiedby' => null,
				'modifiedtime' => null,
				'customized' => $customized,
				'ownedby' => $module->getOwnedby(),
				'version' => $module->getVersion(),
				'parent' => $module->getParent(),
				'isentitytype' => $module->getIsentitytype() ? 1 : 0,
				'type' => $module->getType()
			])->execute();

			// Handle minversion
			if ($module->getMinversion()) {
				$isExists = (new \App\Db\Query())
					->from('vtiger_tab_info')
					->where(['tabid' => $moduleId, 'prefname' => 'vtiger_min_version'])
					->exists();
				if ($isExists) {
					$this->db->createCommand()
						->update('vtiger_tab_info', ['prefvalue' => $module->getMinversion()], 
							['tabid' => $moduleId, 'prefname' => 'vtiger_min_version'])
						->execute();
				} else {
					$this->db->createCommand()->insert('vtiger_tab_info', [
						'tabid' => $moduleId,
						'prefname' => 'vtiger_min_version',
						'prefvalue' => $module->getMinversion()
					])->execute();
				}
			}

			// Handle maxversion
			if ($module->getMaxversion()) {
				$isExists = (new \App\Db\Query())
					->from('vtiger_tab_info')
					->where(['tabid' => $moduleId, 'prefname' => 'vtiger_max_version'])
					->exists();
				if ($isExists) {
					$this->db->createCommand()
						->update('vtiger_tab_info', ['prefvalue' => $module->getMaxversion()], 
							['tabid' => $moduleId, 'prefname' => 'vtiger_max_version'])
						->execute();
				} else {
					$this->db->createCommand()->insert('vtiger_tab_info', [
						'tabid' => $moduleId,
						'prefname' => 'vtiger_max_version',
						'prefvalue' => $module->getMaxversion()
					])->execute();
				}
			}

			// Initialize profile
			$this->initProfile($moduleId, $module->getIsentitytype());

			// Sync file
			$this->syncfile();

			// Initialize sharing if entity type
			if ($module->getIsentitytype()) {
				$this->initSharing($moduleId);
			}

			// Fire postinstall event
			$this->eventDispatcher->fire($module->getName(), 'module.postinstall');

			$transaction->commit();
			return $moduleId;
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Update an existing module.
	 * 
	 * @param int $moduleId
	 * @param Models\Module $module
	 * @return void
	 * @throws \Exception
	 */
	public function update(int $moduleId, Models\Module $module): void
	{
		$transaction = $this->db->beginTransaction();
		try {
			$label = $module->getLabel() ?: $module->getName();
			
			$this->db->createCommand()->update('vtiger_tab', [
				'name' => $module->getName(),
				'tablabel' => $label,
				'presence' => $module->getPresence(),
				'ownedby' => $module->getOwnedby(),
				'version' => $module->getVersion(),
				'parent' => $module->getParent(),
				'isentitytype' => $module->getIsentitytype() ? 1 : 0,
				'type' => $module->getType()
			], ['tabid' => $moduleId])->execute();

			// Update version info
			if ($module->getMinversion()) {
				$isExists = (new \App\Db\Query())
					->from('vtiger_tab_info')
					->where(['tabid' => $moduleId, 'prefname' => 'vtiger_min_version'])
					->exists();
				if ($isExists) {
					$this->db->createCommand()
						->update('vtiger_tab_info', ['prefvalue' => $module->getMinversion()], 
							['tabid' => $moduleId, 'prefname' => 'vtiger_min_version'])
						->execute();
				} else {
					$this->db->createCommand()->insert('vtiger_tab_info', [
						'tabid' => $moduleId,
						'prefname' => 'vtiger_min_version',
						'prefvalue' => $module->getMinversion()
					])->execute();
				}
			}

			if ($module->getMaxversion()) {
				$isExists = (new \App\Db\Query())
					->from('vtiger_tab_info')
					->where(['tabid' => $moduleId, 'prefname' => 'vtiger_max_version'])
					->exists();
				if ($isExists) {
					$this->db->createCommand()
						->update('vtiger_tab_info', ['prefvalue' => $module->getMaxversion()], 
							['tabid' => $moduleId, 'prefname' => 'vtiger_max_version'])
						->execute();
				} else {
					$this->db->createCommand()->insert('vtiger_tab_info', [
						'tabid' => $moduleId,
						'prefname' => 'vtiger_max_version',
						'prefvalue' => $module->getMaxversion()
					])->execute();
				}
			}

			$this->syncfile();
			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Delete a module.
	 * 
	 * @param int $moduleId
	 * @return void
	 * @throws \Exception
	 */
	public function delete(int $moduleId): void
	{
		$module = $this->getInstance($moduleId);
		if (!$module) {
			throw new \Exception("Module with ID $moduleId not found");
		}

		$transaction = $this->db->beginTransaction();
		try {
			// Fire preuninstall event
			$this->eventDispatcher->fire($module->getName(), 'module.preuninstall');

			// Delete cascade
			$this->deleteCascade($moduleId, $module);

			// Delete from vtiger_tab
			$this->db->createCommand()->delete('vtiger_tab', ['tabid' => $moduleId])->execute();

			$this->syncfile();
			\App\Cache\Cache::clear();
			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Get module instance by name or ID.
	 * 
	 * @param string|int $nameOrId
	 * @return Models\Module|null
	 */
	public function getInstance($nameOrId): ?Models\Module
	{
		// Get module data from cache or database
		$id = $name = null;
		if (is_numeric($nameOrId)) {
			$id = $nameOrId;
			if (\App\Cache\Cache::has('moduleTabById', $id)) {
				$data = \App\Cache\Cache::get('moduleTabById', $id);
			}
		} else {
			$name = (string) $nameOrId;
			if (\App\Cache\Cache::has('moduleTabByName', $name)) {
				$data = \App\Cache\Cache::get('moduleTabByName', $name);
			}
		}

		if (!isset($data)) {
			$moduleList = [];
			$rows = (new \App\Db\Query())->from('vtiger_tab')->all();
			foreach ($rows as $row) {
				\App\Cache\Cache::save('moduleTabById', $row['tabid'], $row);
				\App\Cache\Cache::save('moduleTabByName', $row['name'], $row);
				$moduleList[$row['tabid']] = $row;
			}
			\App\Cache\Cache::save('moduleTabs', 'all', $moduleList);
			if ($name && \App\Cache\Cache::has('moduleTabByName', $name)) {
				$data = \App\Cache\Cache::get('moduleTabByName', $name);
			} elseif ($id && \App\Cache\Cache::has('moduleTabById', $id)) {
				$data = \App\Cache\Cache::get('moduleTabById', $id);
			}
		}

		if (empty($data)) {
			return null;
		}

		// Get minversion and maxversion from vtiger_tab_info
		$minversion = false;
		$maxversion = false;
		$tabInfo = (new \App\Db\Query())
			->from('vtiger_tab_info')
			->where(['tabid' => $data['tabid']])
			->all();
		foreach ($tabInfo as $info) {
			if ($info['prefname'] === 'vtiger_min_version') {
				$minversion = $info['prefvalue'];
			} elseif ($info['prefname'] === 'vtiger_max_version') {
				$maxversion = $info['prefvalue'];
			}
		}

		// Initialize entity info if needed
		$basetable = false;
		$basetableid = false;
		$entityidcolumn = false;
		$entityidfield = false;
		$isentitytype = (bool) $data['isentitytype'];
		
		if ($isentitytype || $data['name'] === 'Users') {
			$entitydata = \App\Utils\ModuleUtils::getEntityInfo($data['name']);
			if ($entitydata) {
				$basetable = $entitydata['tablename'];
				$basetableid = $entitydata['entityidfield'];
				$entityidcolumn = $entitydata['entityidcolumn'] ?? false;
				$entityidfield = $entitydata['entityidfield'] ?? false;
			}
		}

		return new Models\Module(
			$data['tabid'],
			$data['name'],
			$data['tablabel'],
			$data['version'],
			$minversion,
			$maxversion,
			$data['presence'],
			$data['ownedby'],
			$data['tabsequence'],
			$data['parent'],
			$data['customized'],
			$isentitytype,
			$entityidcolumn,
			$entityidfield,
			$basetable,
			$basetableid,
			false, // customtable - not stored in vtiger_tab
			false, // grouptable - not stored in vtiger_tab
			$data['type'],
			null // tableName - determined dynamically
		);
	}

	/**
	 * Toggle module access (enable/disable).
	 * 
	 * @param string $moduleName
	 * @param bool $enable
	 * @return void
	 * @throws \Exception
	 */
	public function toggleAccess(string $moduleName, bool $enable): void
	{
		$eventType = $enable ? 'module.enabled' : 'module.disabled';
		$presence = $enable ? 0 : 1;

		$fire = $this->eventDispatcher->fire($moduleName, $eventType);
		if ($fire) {
			$this->db->createCommand()
				->update('vtiger_tab', ['presence' => $presence], ['name' => $moduleName])
				->execute();
			\App\Utils\VtlibUtils::recreateUserPrivilegeFiles();
			$menuRecordModel = new \App\Modules\Settings\Menu\Models\Record();
			$menuRecordModel->refreshMenuFiles();
		}
	}

	/**
	 * Initialize tables for a module.
	 * 
	 * @param int $moduleId
	 * @param Models\Module $module
	 * @return void
	 * @throws \Exception
	 */
	public function initTables(int $moduleId, Models\Module $module): void
	{
		$basetable = $module->getBasetable();
		$basetableid = $module->getBasetableid();
		$customtable = $module->getCustomtable();

		// Initialize tablename and index column names
		$lcasemodname = strtolower($module->getName());
		if (!$basetable) {
			$basetable = "vtiger_$lcasemodname";
		}
		if (!$basetableid) {
			$basetableid = $lcasemodname . 'id';
		}
		if (!$customtable) {
			$customtable = $basetable . 'cf';
		}

		$importer = new \App\Db\Importers\Base();
		
		// Create basetable
		$this->db->createTable($basetable, [
			$basetableid => 'int'
		]);
		$this->db->createCommand()
			->addPrimaryKey("{$basetable}_pk", $basetable, $basetableid)
			->execute();
		$this->db->createCommand()->addForeignKey(
			"fk_1_{$basetable}{$basetableid}", 
			$basetable, 
			$basetableid, 
			'vtiger_crmentity', 
			'crmid', 
			'CASCADE', 
			'RESTRICT'
		)->execute();

		// Create customtable
		$this->db->createTable($customtable, [
			$basetableid => 'int'
		]);
		$this->db->createCommand()
			->addPrimaryKey("{$customtable}_pk", $customtable, $basetableid)
			->execute();
		$this->db->createCommand()->addForeignKey(
			"fk_1_{$customtable}{$basetableid}", 
			$customtable, 
			$basetableid, 
			$basetable, 
			$basetableid, 
			'CASCADE', 
			'RESTRICT'
		)->execute();

		// Create inventory tables if type=1
		if ($module->getType() === 1) {
			$this->db->createTable($basetable . '_invfield', [
				'id' => 'pk',
				'columnname' => 'string(30)',
				'label' => $importer->stringType(50)->notNull(),
				'invtype' => $importer->stringType(30)->notNull(),
				'presence' => $importer->boolean()->defaultValue(false),
				'defaultvalue' => 'string',
				'sequence' => $importer->smallInteger()->unsigned()->notNull(),
				'block' => $importer->smallInteger()->unsigned()->notNull(),
				'displaytype' => $importer->smallInteger()->unsigned()->notNull()->defaultValue(1),
				'params' => 'text',
				'colspan' => $importer->smallInteger()->unsigned()->notNull()->defaultValue(1),
			]);
			$this->db->createTable($basetable . '_inventory', [
				'id' => 'int'
			]);
			$this->db->createCommand()
				->createIndex("{$basetable}_inventory_id_idx", $basetable . '_inventory', 'id')
				->execute();
			$this->db->createCommand()->addForeignKey(
				"fk_1_{$basetable}_inventory{$basetableid}", 
				$basetable . '_inventory', 
				'id', 
				$basetable, 
				$basetableid, 
				'CASCADE', 
				'RESTRICT'
			)->execute();
			$this->db->createTable($basetable . '_invmap', [
				'module' => $importer->stringType(50)->notNull(),
				'field' => $importer->stringType(50)->notNull(),
				'tofield' => $importer->stringType(50)->notNull()
			]);
			$this->db->createCommand()
				->addPrimaryKey("{$basetable}_invmap_pk", $basetable . '_invmap', ['module', 'field', 'tofield'])
				->execute();
		}
	}

	/**
	 * Initialize webservice for a module.
	 * 
	 * @param int $moduleId
	 * @return void
	 */
	public function initWebservice(int $moduleId): void
	{
		$module = $this->getInstance($moduleId);
		if ($module) {
			\App\ModuleManagement\ServiceLocator::getWebserviceService()->initialize(
				$moduleId,
				$module->getName(),
				$module->getIsentitytype()
			);
		}
	}

	/**
	 * Set entity identifier field for a module.
	 * 
	 * @param int $moduleId
	 * @param int $fieldId
	 * @return void
	 * @throws \Exception
	 */
	public function setEntityIdentifier(int $moduleId, int $fieldId): void
	{
		$module = $this->getInstance($moduleId);
		if (!$module) {
			throw new \Exception("Module with ID $moduleId not found");
		}

		$fieldService = \App\ModuleManagement\ServiceLocator::getFieldService();
		$field = $fieldService->getInstance($fieldId, $module);
		if (!$field) {
			throw new \Exception("Field with ID $fieldId not found");
		}

		$basetableid = $module->getBasetableid();
		$entityidfield = $module->getEntityidfield() ?: $basetableid;
		$entityidcolumn = $module->getEntityidcolumn() ?: $basetableid;

		if ($entityidfield && $entityidcolumn) {
			$isExists = (new \App\Db\Query())
				->from('vtiger_entityname')
				->where(['tablename' => $field->getTable(), 'tabid' => $moduleId])
				->exists();
			if (!$isExists) {
				$this->db->createCommand()->insert('vtiger_entityname', [
					'tabid' => $moduleId,
					'modulename' => $module->getName(),
					'tablename' => $field->getTable(),
					'fieldname' => $field->getName(),
					'entityidfield' => $entityidfield,
					'entityidcolumn' => $entityidcolumn,
					'searchcolumn' => $field->getName()
				])->execute();
			} else {
				$this->db->createCommand()->update('vtiger_entityname', [
					'fieldname' => $field->getName(),
					'entityidfield' => $entityidfield,
					'entityidcolumn' => $module->getName()
				], ['tabid' => $moduleId, 'tablename' => $field->getTable()])->execute();
			}
		}
	}

	/**
	 * Initialize profile for module.
	 * 
	 * @param int $moduleId
	 * @param bool $isentitytype
	 * @return void
	 */
	private function initProfile(int $moduleId, bool $isentitytype): void
	{
		\App\ModuleManagement\ServiceLocator::getProfileService()->initForModule($moduleId, $isentitytype);
	}

	/**
	 * Initialize sharing access for module.
	 * 
	 * @param int $moduleId
	 * @return void
	 */
	private function initSharing(int $moduleId): void
	{
		\App\ModuleManagement\ServiceLocator::getAccessService()->initSharing($moduleId);
	}

	/**
	 * Delete cascade operations for module.
	 * 
	 * @param int $moduleId
	 * @param Models\Module $module
	 * @return void
	 */
	private function deleteCascade(int $moduleId, Models\Module $module): void
	{
		$moduleInstance = \Vtiger_Module_Model::getInstance($module->getName());
		$focus = \App\Core\CRMEntity::getInstance($module->getName());
		$tableName = $focus->table_name ?? null;

		if ($module->getIsentitytype()) {
			// Delete from CRMEntity
			$this->deleteFromCRMEntity($module->getName());
			
			// Delete tools
			\App\ModuleManagement\ServiceLocator::getAccessService()->deleteTools($moduleId);
			
			// Delete filters
			\App\ModuleManagement\ServiceLocator::getFilterService()->deleteForModule($moduleId);
			
			// Delete blocks
			\App\ModuleManagement\ServiceLocator::getBlockService()->deleteForModule($moduleId, false);
			
			// Deinit webservice
			\App\ModuleManagement\ServiceLocator::getWebserviceService()->uninitialize(
				$module->getName(),
				$module->getIsentitytype()
			);
		}

		// Delete icons
		$this->deleteIcons($module->getName());

		// Unset all related lists
		$this->unsetAllRelatedList($moduleId);

		// Delete ModComments
		\App\Modules\ModComments\Models\Module::deleteForModule($moduleInstance);

		// Delete language files
		\App\ModuleManagement\ServiceLocator::getLanguageService()->deleteForModule($module->getName());

		// Delete sharing access
		\App\ModuleManagement\ServiceLocator::getAccessService()->deleteSharing($moduleId);

		// Delete modentity_num
		$this->db->createCommand()->delete('vtiger_modentity_num', ['tabid' => $moduleId])->execute();

		// Delete cron tasks
		\App\ModuleManagement\ServiceLocator::getCronService()->deleteForModule($module->getName());

		// Delete profiles
		\App\ModuleManagement\ServiceLocator::getProfileService()->deleteForModule($moduleId);

		// Delete workflows
		\App\Modules\Settings\Workflows\Models\Module::deleteForModule($moduleInstance);

		// Delete menu
		\App\ModuleManagement\ServiceLocator::getMenuService()->deleteForModule($moduleId);

		// Delete group2modules
		$this->db->createCommand()->delete('vtiger_group2modules', ['tabid' => $moduleId])->execute();

		// Delete tables
		if ($tableName) {
			$this->deleteModuleTables($module, $tableName);
		}

		// Delete CRMEntityRel
		$this->db->createCommand()
			->delete('vtiger_crmentityrel', ['or', ['module' => $module->getName()], ['relmodule' => $module->getName()]])
			->execute();

		// Delete links
		\App\ModuleManagement\ServiceLocator::getLinkService()->deleteAll($moduleId);

		// Delete settings fields
		\App\Modules\Settings\Vtiger\Models\Module::deleteSettingsFieldBymodule($module->getName());

		// Delete directory
		$this->deleteDir($moduleInstance);
	}

	/**
	 * Delete from CRMEntity.
	 * 
	 * @param string $moduleName
	 * @return void
	 */
	private function deleteFromCRMEntity(string $moduleName): void
	{
		$query = (new \App\Db\Query())
			->select(['crmid'])
			->from('vtiger_crmentity')
			->where(['setype' => $moduleName]);
		$dataReader = $query->createCommand()->query();
		while ($id = $dataReader->readColumn(0)) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($id, $moduleName);
			$recordModel->delete();
		}
		$this->db->createCommand()->delete('vtiger_crmentity', ['setype' => $moduleName])->execute();
	}

	/**
	 * Delete module tables.
	 * 
	 * @param Models\Module $module
	 * @param string $tableName
	 * @return void
	 */
	private function deleteModuleTables(Models\Module $module, string $tableName): void
	{
		$this->db->createCommand()->checkIntegrity(false)->execute();
		$moduleInstance = \Vtiger_Module_Model::getInstance($module->getName());
		
		if ($moduleInstance->isInventory()) {
			$tablesName = [$tableName . '_inventory', $tableName . '_invfield', $tableName . '_invmap'];
			foreach ($tablesName as $tblName) {
				if ($this->db->isTableExists($tblName)) {
					$this->db->createCommand()->dropTable($tblName)->execute();
				}
			}
		}
		
		if (!empty($tableName)) {
			$tablesName = [$tableName . 'cf', $tableName];
			foreach ($tablesName as $tblName) {
				if ($this->db->isTableExists($tblName)) {
					$this->db->createCommand()->dropTable($tblName)->execute();
				}
			}
		}
		
		$this->db->createCommand()->checkIntegrity(true)->execute();
	}

	/**
	 * Delete icons.
	 * 
	 * @param string $moduleName
	 * @return void
	 */
	private function deleteIcons(string $moduleName): void
	{
		$iconSize = ['', 48, 64, 128];
		foreach ($iconSize as $value) {
			foreach (\App\Runtime\Yeti_Layout::getAllLayouts() as $name => $label) {
				$fileName = "layouts/$name/skins/images/" . $moduleName . $value . ".png";
				if (file_exists($fileName)) {
					@unlink($fileName);
				}
			}
		}
	}

	/**
	 * Delete directory.
	 * 
	 * @param object $moduleInstance
	 * @return void
	 */
	private function deleteDir($moduleInstance): void
	{
		$fileService = \App\ModuleManagement\ServiceLocator::getFileService();
		$fileService->recurseDelete("config/modules/{$moduleInstance->name}.php");
		$fileService->recurseDelete('modules/' . $moduleInstance->name);
		$fileService->recurseDelete('modules/Settings/' . $moduleInstance->name);
		foreach (\App\Runtime\Yeti_Layout::getAllLayouts() as $name => $label) {
			$fileService->recurseDelete("layouts/$name/modules/{$moduleInstance->name}");
			$fileService->recurseDelete("layouts/$name/modules/Settings/{$moduleInstance->name}");
		}
	}

	/**
	 * Unset all related lists.
	 * 
	 * @param int $moduleId
	 * @return void
	 */
	private function unsetAllRelatedList(int $moduleId): void
	{
		$ids = (new \App\Db\Query())
			->select(['relation_id'])
			->from('vtiger_relatedlists')
			->where(['or', ['tabid' => $moduleId], ['related_tabid' => $moduleId]])
			->column();
		$this->db->createCommand()
			->delete('vtiger_relatedlists', ['or', ['tabid' => $moduleId], ['related_tabid' => $moduleId]])
			->execute();
		if ($ids) {
			$this->db->createCommand()
				->delete('vtiger_relatedlists_fields', ['relation_id' => $ids])
				->execute();
			$this->db->createCommand()
				->delete('a_yf_relatedlists_inv_fields', ['relation_id' => $ids])
				->execute();
		}
	}

	/**
	 * Sync file (create module meta file).
	 * 
	 * @return void
	 */
	private function syncfile(): void
	{
		\vtlib\Deprecated::createModuleMetaFile();
	}

	/**
	 * Create vtlib Module instance for compatibility.
	 * 
	 * @param Models\Module $module
	 * @return \vtlib\Module
	 */
	private function createVtlibModuleInstance(Models\Module $module): \vtlib\Module
	{
		$vtlibModule = new \vtlib\Module();
		$vtlibModule->id = $module->getId();
		$vtlibModule->name = $module->getName();
		$vtlibModule->label = $module->getLabel();
		$vtlibModule->version = $module->getVersion();
		$vtlibModule->presence = $module->getPresence();
		$vtlibModule->ownedby = $module->getOwnedby();
		$vtlibModule->tabsequence = $module->getTabsequence();
		$vtlibModule->parent = $module->getParent();
		$vtlibModule->customized = $module->getCustomized();
		$vtlibModule->isentitytype = $module->getIsentitytype();
		$vtlibModule->entityidcolumn = $module->getEntityidcolumn();
		$vtlibModule->entityidfield = $module->getEntityidfield();
		$vtlibModule->basetable = $module->getBasetable();
		$vtlibModule->basetableid = $module->getBasetableid();
		$vtlibModule->customtable = $module->getCustomtable();
		$vtlibModule->grouptable = $module->getGrouptable();
		$vtlibModule->type = $module->getType();
		return $vtlibModule;
	}
}
