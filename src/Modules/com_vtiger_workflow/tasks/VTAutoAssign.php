<?php

namespace FreeCRM\Modules\com_vtiger_workflow\tasks;

/**
 * Auto assign records Task Class
 * @package YetiForce.WorkflowTask
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
require_once('src/Modules/com_vtiger_workflow/VTEntityCache.php');
require_once('src/Modules/com_vtiger_workflow/VTWorkflowUtils.php');

class VTAutoAssign extends VTTask
{

	public $executeImmediately = true;

	public function getFieldNames()
	{
		return ['template'];
	}

	public function doTask($recordModel)
	{
		\Settings_AutomaticAssignment_Module_Model::autoAssignExecute($recordModel);
	}

	public function getAutoAssignEntries($moduleName)
	{
		$moduleName = \App\Module::getTabName($moduleName);
		$listViewModel = Settings_\FreeCRM\Modules\Vtiger\Models\ListView::getInstance('Settings:AutomaticAssignment');
		$listViewModel->set('sourceModule', \App\Module::getModuleId($moduleName));
		$entries = $listViewModel->getListViewEntries(new \FreeCRM\Modules\Vtiger\Models\Paging());
		return $entries;
	}
}
