<?php

namespace App\Modules\Settings\SharingAccess\Views;
use App\Modules\Settings\SharingAccessModels\RuleMember;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


class Settings_SharingAccess_IndexAjax_View extends \App\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showRules');
		$this->exposeMethod('editRule');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function showRules(\App\Http\Vtiger_Request $request)
	{

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$forModule = $request->get('for_module');

		$moduleModel = \App\Modules\Settings\SharingAccess\Models\Module::getInstance($forModule);
		$ruleModelList = \App\Modules\Settings\SharingAccess\Models\Rule::getAllByModule($moduleModel);

		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('FOR_MODULE', $forModule);
		$viewer->assign('RULE_MODEL_LIST', $ruleModelList);
		$viewer->assign('USER_MODEL', $request->getUser());

		echo $viewer->view('ListRules.tpl', $qualifiedModuleName, true);
	}

	public function editRule(\App\Http\Vtiger_Request $request)
	{

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$forModule = $request->get('for_module');
		$ruleId = $request->get('record');

		$moduleModel = \App\Modules\Settings\SharingAccess\Models\Module::getInstance($forModule);
		if ($ruleId) {
			$ruleModel = \App\Modules\Settings\SharingAccess\Models\Rule::getInstance($moduleModel, $ruleId);
		} else {
			$ruleModel = new \App\Modules\Settings\SharingAccess\Models\Rule();
			$ruleModel->setModuleFromInstance($moduleModel);
		}

		$viewer->assign('ALL_RULE_MEMBERS', \App\Modules\Settings\SharingAccess\Models\RuleMember::getAll());
		$viewer->assign('ALL_PERMISSIONS', \App\Modules\Settings\SharingAccess\Models\Rule::$allPermissions);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('RULE_MODEL', $ruleModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', $request->getUser());

		echo $viewer->view('EditRule.tpl', $qualifiedModuleName, true);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
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
