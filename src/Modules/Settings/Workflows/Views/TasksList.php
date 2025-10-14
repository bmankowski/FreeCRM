<?php

namespace FreeCRM\Modules\Settings\Workflows\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

use FreeCRM\Modules\Settings\Workflows\Models\Record as Settings_Workflows_Record_Model;
class TasksList extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordId = $request->get('record');
		$workflowModel = Settings_Workflows_Record_Model::getInstance($recordId);

		$viewer->assign('WORKFLOW_MODEL', $workflowModel);

		$viewer->assign('TASK_LIST', $workflowModel->getTasks());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('TasksList.tpl', $qualifiedModuleName);
	}
}
