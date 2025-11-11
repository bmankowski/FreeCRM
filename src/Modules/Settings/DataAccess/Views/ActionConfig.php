<?php

namespace App\Modules\Settings\DataAccess\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class ActionConfig extends \App\Modules\Settings\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$moduleName = $request->getModule();
		$baseModule = $request->get('m');
		$tplId = $request->get('did');
		$aid = $request->get('aid');
		$action = $request->get('an');
		$actionsName = explode(\App\Modules\Settings\DataAccess\Models\Module::$separator, $action);
		$Config = \App\Modules\Settings\DataAccess\Models\Module::showConfigDataAccess($tplId, $action, $baseModule);
		$DataAccess = \App\Modules\Settings\DataAccess\Models\Module::getDataAccessInfo($tplId, false);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('BASE_MODULE', $baseModule);
		$viewer->assign('ACTIONMOD', $actionsName[0]);
		$viewer->assign('ACTION', $actionsName[1]);
		$viewer->assign('ACTIONNAME', $action);
		$viewer->assign('AID', $aid);
		$viewer->assign('TPL_ID', $tplId);
		$viewer->assign('CONFIG', $Config);
		$viewer->assign('SAVED_DATA', $DataAccess['basic_info']['data'][$aid]);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		
		// Prepare DataAccess ActionConfig-specific data for ActionConfig template
		$this->prepareDataAccessActionConfigData($viewer, $action);
		
		echo $viewer->view('ActionConfig.tpl', $qualifiedModuleName, true);
	}
	
	/**
	 * Prepare data for DataAccess ActionConfig template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareDataAccessActionConfigData($viewer, $actionName)
	{
		$viewer->assign('ACTION_NAME_SHORT', \App\Modules\Settings\DataAccess\Models\Module::getActionName($actionName, true));
		$viewer->assign('ACTION_NAME_FULL', \App\Modules\Settings\DataAccess\Models\Module::getActionName($actionName, false));
	}
}
