<?php

namespace App\Modules\Settings\ModuleManager\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Module extends \App\Modules\Base\Models\Module
{

	public static function getNonVisibleModulesList()
	{
		return ['ModTracker', 'Portal', 'Users', 'Integration', 'WSAPP',
			'ConfigEditor', 'FieldFormulas', 'VtigerBackup', 'CronTasks', 'Import', 'Tooltip',
			'Home'];
	}

	/**
	 * Function to get the url of new module import
	 */
	public static function getNewModuleImportUrl()
	{
		return 'index.php?module=ModuleManager&parent=Settings&view=ModuleImport';
	}

	/**
	 * Function to get the url of new module import 
	 */
	public static function getUserModuleImportUrl()
	{
		return 'index.php?module=ModuleManager&parent=Settings&view=ModuleImport&mode=importUserModuleStep1';
	}

	/**
	 * Function to disable a module 
	 * @param string $moduleName - name of the module
	 */
	public function disableModule($moduleName)
	{
		//Handling events after disable module
		\vtlib\Module::toggleModuleAccess($moduleName, false);
	}

	/**
	 * Function to enable the module
	 * @param string $moduleName -- name of the module
	 */
	public function enableModule($moduleName)
	{
		//Handling events after enable module
		\vtlib\Module::toggleModuleAccess($moduleName, true);
	}

	/**
	 * Static Function to get the instance of Vtiger Module Model for all the modules
	 * @return array - List of Vtiger Module Model or sub class instances
	 */
	public static function getAll($presence = [], $restrictedModulesList = [], $isEntityType = false)
	{
		return parent::getAll(array(0, 1), self::getNonVisibleModulesList());
	}

	/**
	 * Function which will get count of modules
	 * @param boolean $onlyActive - if true get count of only active modules else all the modules
	 * @return int number of modules
	 */
	public static function getModulesCount($onlyActive = false)
	{
		$query = (new \App\Db\Query)->from('vtiger_tab');
		if ($onlyActive) {
			$nonVisibleModules = self::getNonVisibleModulesList();
			$query->where(['and', ['presence' => 0], ['NOT IN', 'name', $nonVisibleModules]]);
		}
		return $query->count();
	}

	/**
	 * Function that returns all those modules that support Module Sequence Numbering
	 * @return array
	 */
	public static function getModulesSupportingSequenceNumbering()
	{
		$subQuery = (new \App\Db\Query())->select('tabid')->from('vtiger_field')->where(['uitype' => 4])->distinct();
		$dataReader = (new \App\Db\Query())->select(['tabid', 'name'])
				->from('vtiger_tab')
				->where(['isentitytype' => 1, 'presence' => 0, 'tabid' => $subQuery])
				->createCommand()->query();
		$moduleModels = [];
		while ($row = $dataReader->read()) {
			$moduleModels[$row['name']] = self::getInstanceFromArray($row);
		}
		return $moduleModels;
	}

	/**
	 * Function to get restricted modules list
	 * @return array List module names
	 */
	public static function getActionsRestrictedModulesList()
	{
		return ['Home'];
	}

	public static function createModule($moduleInformation)
	{
		$moduleInformation['entityfieldname'] = strtolower(self::toAlphaNumeric($moduleInformation['entityfieldname']));

		$moduleName = ucfirst($moduleInformation['module_name']);
		if (\App\Modules\Base\Models\Module::getInstance($moduleName)) {
			throw new \App\Exceptions\AppException('Module already exists: ' . $moduleName);
		}

		$module = new \vtlib\Module();
		$module->name = $moduleName;
		$module->label = $moduleInformation['module_label'];
		$module->type = (int) $moduleInformation['entitytype'];
		$module->save();
		$module->initTables();

		$entityField = new \stdClass();
		$entityField->name = $moduleInformation['entityfieldname'];
		$entityField->label = $moduleInformation['entityfieldlabel'];
		$entityField->column = $entityField->name;
		$module->createFiles($entityField);

		$block = new \vtlib\Block();
		$block->label = 'LBL_BASIC_INFORMATION';
		$module->addBlock($block);

		$blockcf = new \vtlib\Block();
		$blockcf->label = 'LBL_CUSTOM_INFORMATION';
		$module->addBlock($blockcf);

		$field1 = new \stdClass();
		$field1->name = $moduleInformation['entityfieldname'];
		$field1->label = $moduleInformation['entityfieldlabel'];
		$field1->table = $module->basetable;
		$field1->uitype = 2;
		$field1->column = $field1->name;
		$field1->columntype = 'string(255)';
		$field1->typeofdata = 'V';
		$field1->mandatory = 1;
		$block->addField($field1);

		$module->setEntityIdentifier($field1);

		$field2 = new \stdClass();
		$field2->name = 'number';
		$field2->label = 'FL_NUMBER';
		$field2->column = 'number';
		$field2->table = $module->basetable;
		$field2->uitype = 4;
		$field2->typeofdata = 'V';
		$field2->columntype = 'string(32)';
		$block->addField($field2);

		$field3 = new \stdClass();
		$field3->name = 'assigned_user_id';
		$field3->label = 'Assigned To';
		$field3->table = 'vtiger_crmentity';
		$field3->column = 'smownerid';
		$field3->uitype = 53;
		$field3->typeofdata = 'V';
		$field3->mandatory = 1;
		$block->addField($field3);

		$field4 = new \stdClass();
		$field4->name = 'createdtime';
		$field4->label = 'Created Time';
		$field4->table = 'vtiger_crmentity';
		$field4->column = 'createdtime';
		$field4->uitype = 70;
		$field4->typeofdata = 'DT';
		$field4->displaytype = 2;
		$block->addField($field4);

		$field5 = new \stdClass();
		$field5->name = 'modifiedtime';
		$field5->label = 'Modified Time';
		$field5->table = 'vtiger_crmentity';
		$field5->column = 'modifiedtime';
		$field5->uitype = 70;
		$field5->typeofdata = 'DT';
		$field5->displaytype = 2;
		$block->addField($field5);

		$field6 = new \stdClass();
		$field6->name = 'created_user_id';
		$field6->label = 'Created By';
		$field6->table = 'vtiger_crmentity';
		$field6->column = 'smcreatorid';
		$field6->uitype = 53;
		$field6->typeofdata = 'V';
		$field6->displaytype = 2;
		$field6->quickcreate = 3;
		$field6->masseditable = 0;
		$block->addField($field6);

		// Create default custom filter (mandatory)
		$filter1 = new \vtlib\Filter();
		$filter1->name = 'All';
		$filter1->isdefault = true;
		$filter1->presence = 0;
		$module->addFilter($filter1);
		// Add fields to the filter created
		$filter1->addField($field1)->addField($field2, 1)->addField($field3, 2)->addField($field4, 2);

		// Set sharing access of this module
		$module->setDefaultSharing();

		// Enable and Disable available tools
		$module->enableTools(['Import', 'Export', 'DuplicatesHandling', 'CreateCustomFilter',
			'DuplicateRecord', 'MassEdit', 'MassDelete', 'MassAddComment', 'MassTransferOwnership',
			'ReadRecord', 'WorkflowTrigger', 'Dashboard', 'CreateDashboardFilter',
			'QuickExportToExcel', 'DetailTransferOwnership', 'ExportPdf',
			'RecordMapping', 'RecordMappingList', 'FavoriteRecords', 'WatchingRecords',
			'WatchingModule', 'RemoveRelation', 'ReviewingUpdates']);

		// Initialize Webservice support
		$module->initWebservice();

		\App\Fields\RecordNumber::setNumber($module->id, 'N', 1);

		\App\ModuleManagement\ServiceLocator::getEventDispatcher()
			->fire($module->name, 'module.postinstall');

		\App\Utils\VtlibUtils::recreateUserPrivilegeFiles();
	}

	public static function toAlphaNumeric($value)
	{
		return preg_replace("/[^a-zA-Z0-9_]/", '', $value);
	}

	public static function getUploadDirectory()
	{
		$uploadDir = 'cache/vtlib';
		return $uploadDir;
	}
}
