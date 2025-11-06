<?php

namespace App\Modules\Base\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


class Index extends \App\Modules\Base\Views\Basic
{

	public function __construct()
	{
		parent::__construct();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if (!empty($moduleName)) {
			$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
			$permission = $userPrivilegesModel->hasModulePermission($moduleName);

			if (!$permission) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		// MainLayout handles rendering, no separate preProcess template needed
	}
	
	protected function assignSidebarData(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		if (!empty($moduleName)) {
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
			if (!$moduleModel) {
				// Non-entity or unsupported module (e.g. Home); skip permission block
				$viewer->assign('CURRENT_VIEW', $request->get('view'));
				return;
			}
			$currentUser = $request->getUser();
			$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getInstanceById($currentUser->getId());
			$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
			if (!$permission) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}

			$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
			$linkModels = $moduleModel->getSideBarLinks($linkParams);
			
			// Process sidebar links to determine active link
			$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);

			$viewer->assign('QUICK_LINKS', $linkModels);
			$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);
		}
		$viewer->assign('CURRENT_VIEW', $request->get('view'));
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		// MainLayout handles footer rendering, no separate postProcess template needed
		parent::postProcess($request);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->assignSidebarData($request);
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$viewer->view('Index.tpl', $moduleName);
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
		$view = $request->get('view');

		$jsFileNames = array(
			'modules.Base.resources.Vtiger',
			'modules.Base.resources.' . $view,
			"modules.$moduleName.resources.$moduleName",
			"modules.$moduleName.resources.$view",
			'libraries.jquery.ckeditor.ckeditor',
			'libraries.jquery.ckeditor.adapters.jquery',
			'modules.Base.resources.CkEditor',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateReadAccess();
	}
}
