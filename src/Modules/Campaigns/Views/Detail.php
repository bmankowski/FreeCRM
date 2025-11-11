<?php

namespace App\Modules\Campaigns\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


class Detail extends \App\Modules\Base\Views\Detail
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('showCountRecords');
	}

	public function showCountRecords($request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$relatedModules = $request->get('relatedModules');
		$relatedModulesNames = [];
		foreach ($relatedModules as $tabId) {
			$relatedModulesNames[$tabId] = \App\Utils\ModuleUtils::getModuleName($tabId);
		}
		$countRecords = \App\Modules\Base\Widgets\CountRecords::getCountRecords($relatedModulesNames, $recordId);
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('COUNT_RECORDS', $countRecords);
		$viewer->assign('RELATED_MODULES', $relatedModulesNames);
		return $viewer->view('CountRecordsContent.tpl', $moduleName, true);
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Base\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Base.resources.ListView',
			"modules.$moduleName.resources.ListView",
			'modules.CustomView.resources.CustomView',
			"modules.$moduleName.resources.CustomView",
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}
}
