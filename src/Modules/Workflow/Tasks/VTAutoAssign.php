<?php

namespace App\Modules\Workflow\Tasks;
\nuse App\Modules\Workflow\VTTask;

/**
 * Auto assign records Task Class
 * @package YetiForce.WorkflowTask
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */


class VTAutoAssign extends VTTask
{

	public $executeImmediately = true;

	public function getFieldNames(): array
	{
		return ['template'];
	}

	public function doTask($recordModel)
	{
		\App\Modules\Settings\AutomaticAssignment\Models\Module::autoAssignExecute($recordModel);
	}

	public function getAutoAssignEntries($moduleName)
	{
		$moduleName = \App\Module::getTabName($moduleName);
		$listViewModel = \App\Modules\Settings\Vtiger\Models\ListView::getInstance('Settings:AutomaticAssignment');
		$listViewModel->set('sourceModule', \App\Module::getModuleId($moduleName));
		$entries = $listViewModel->getListViewEntries(new \App\Modules\Vtiger\Models\Paging());
		return $entries;
	}
}
