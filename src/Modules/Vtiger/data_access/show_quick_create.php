<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */
/*
  Return Description
  ------------------------
  Info type: error, info, success
  Info title: optional
  Info text: mandatory
  Type: 0 - notify
  Type: 1 - show quick create mondal
 */

Class DataAccess_show_quick_create
{

	public $config = true;

	public function process($moduleName, $id, $record_form, $config)
	{
		$db = \App\Database\database\PearDatabase::getInstance();
		$userPrivModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!isset($id) || $id == 0 || $id == '' || !$userPrivModel->hasModuleActionPermission($config['modules'], 'CreateView')) {
			return ['save_record' => true];
		}
		$title = '';
		$instance = \App\Modules\Vtiger\Models\Record::getInstanceById($id, $moduleName);
		if ($instance) {
			$title = $instance->getName();
		}
		return [
			'save_record' => false,
			'type' => 1,
			'module' => $config['modules'],
			'title' => $title,
		];
	}

	public function getConfig($id, $module, $baseModule)
	{
		$db = \App\Database\database\PearDatabase::getInstance();
		$modulesQuickCreate = \App\Modules\Vtiger\Models\Module::getQuickCreateModules(true);
		$modules = [];
		foreach ($modulesQuickCreate as $moduleName => $moduleModel) {
			$quickCreateModule = $moduleModel->isQuickCreateSupported();
			$singularLabel = $moduleModel->getSingularLabelKey();
			if ($singularLabel == 'SINGLE_Calendar') {
				$singularLabel = 'LBL_EVENT_OR_TASK';
			}
			if ($quickCreateModule == 1) {
				$modules[$moduleName] = $singularLabel;
			}
		}
		return Array('modules' => $modules);
	}
}
