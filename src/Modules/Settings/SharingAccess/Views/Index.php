<?php

namespace FreeCRM\Modules\Settings\SharingAccess\Views;
use FreeCRM\Modules\Settings\SharingAccessModels\Action;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

use FreeCRM\Modules\Settings\SharingAccess\Models\Module as Settings_SharingAccess_Module_Model;
Class Settings_SharingAccess_Index_View extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$viewer->assign('ALL_MODULES', Settings_SharingAccess_Module_Model::getAll(true));
		$viewer->assign('ALL_ACTIONS', \FreeCRM\Modules\Settings\SharingAccess\Models\Action::getAll());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USER_MODEL', \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->assign('DEPENDENT_MODULES', Settings_SharingAccess_Module_Model::getDependentModules());

		$viewer->view('Index.tpl', $qualifiedModuleName);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \FreeCRM\Http\Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\FreeCRM\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Settings.Vtiger.resources.Index',
			"modules.Settings.$moduleName.resources.Index"
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
