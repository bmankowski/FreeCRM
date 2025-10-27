<?php

namespace App\Modules\Settings\Roles\Views;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Popup extends \App\Modules\Base\Views\Header
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if (!$currentUser->isAdminUser()) {
			throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$sourceRecord = $request->get('src_record');

		$sourceRole = \App\Modules\Settings\Roles\Models\Record::getInstanceById($sourceRecord);
		$rootRole = \App\Modules\Settings\Roles\Models\Record::getBaseRole();
		$allRoles = \App\Modules\Settings\Roles\Models\Record::getAll();

		$viewer->assign('SOURCE_ROLE', $sourceRole);
		$viewer->assign('ROOT_ROLE', $rootRole);
		$viewer->assign('ROLES', $allRoles);
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('TYPE', $request->get('type'));
		$viewer->assign('MODULE_NAME', $moduleName);

		$viewer->view('Popup.tpl', $qualifiedModuleName);
	}

	/**
	 * Post process - renders lightweight popup footer (no full app footer)
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('FOOTER_SCRIPTS', $this->getFooterScripts($request));
		// Popups don't need the full Footer.tpl, they can render minimal or no footer
		// You could create a PopupFooter.tpl if needed, or just render scripts inline
		echo '</body></html>';
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of ScriptAsset instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

		$jsFileNames = array(
			'modules.Settings.Vtiger.resources.Popup',
			"modules.Settings.$moduleName.resources.Popup",
			"modules.Settings.$moduleName.resources.$moduleName",
			'libraries.jquery.jquery_windowmsg',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	protected function showBodyHeader()
	{
		return false;
	}
}
