<?php

namespace App\Modules\Vtiger\Views;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */


use App\Http\Vtiger_Request;

class WorkflowTrigger extends \App\Modules\Vtiger\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if (!(\App\Modules\Users\Models\Privileges::isPermitted($request->getModule(), 'WorkflowTrigger', $request->get('record')))) {
			throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$workflows = (new \App\Modules\Workflow\VTWorkflowManager(\App\Database\PearDatabase::getInstance()))->getWorkflowsForModule($moduleName, \App\Modules\Workflow\VTWorkflowManager::$TRIGGER);
		foreach ($workflows as $id => $workflow) {
			if (!$workflow->evaluate(\App\Modules\Vtiger\Models\Record::getInstanceById($record))) {
				unset($workflows[$id]);
			}
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD', $record);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('WORKFLOWS', $workflows);
		$viewer->assign('USER_MODEL', $request->getUser());
		$viewer->view('WorkflowTrigger.tpl', $moduleName);
	}
}
