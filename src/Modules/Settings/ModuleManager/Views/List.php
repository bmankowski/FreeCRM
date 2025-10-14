<?php

namespace FreeCRM\Modules\Settings\ModuleManager\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

Class Settings_ModuleManager_List_View extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$viewer->assign('ALL_MODULES', \FreeCRM\Modules\Settings\ModuleManager\Models\Module::getAll());
		$viewer->assign('RESTRICTED_MODULES_LIST', \FreeCRM\Modules\Settings\ModuleManager\Models\Module::getActionsRestrictedModulesList());
		$viewer->assign('IMPORT_MODULE_URL', \FreeCRM\Modules\Settings\ModuleManager\Models\Module::getNewModuleImportUrl());
		$viewer->assign('IMPORT_USER_MODULE_URL', \FreeCRM\Modules\Settings\ModuleManager\Models\Module::getUserModuleImportUrl());
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USER_MODEL', Users_Record_Model::getCurrentUserModel());

		echo $viewer->view('ListContents.tpl', $qualifiedModuleName, true);
	}
}
