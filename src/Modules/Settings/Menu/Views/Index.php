<?php

namespace App\Modules\Settings\Menu\Views;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$roleId = $request->get('roleid');
		if (empty($roleId))
			$roleId = 0;
		$settingsModel = \App\Modules\Settings\Menu\Models\Record::getCleanInstance();
		$rolesContainMenu = $settingsModel->getRolesContainMenu();
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE_MODEL', $settingsModel);
		$viewer->assign('ROLES_CONTAIN_MENU', $rolesContainMenu);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('ROLEID', $roleId);
		$data = $settingsModel->getAll(filter_var($roleId, FILTER_SANITIZE_NUMBER_INT));
		$viewer->assign('DATA', $data);
		$viewer->assign('LASTID', \App\Modules\Settings\Menu\Models\Module::getLastId());
		
		// Prepare Menu IndexContent-specific data for IndexContent template
		$this->prepareMenuIndexContentData($viewer, $data);
		
		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('IndexView.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for Menu IndexContent template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareMenuIndexContentData($viewer, $data)
	{
		// Prepare JSON-encoded data with toSafeHTML
		$dataJson = \App\Json::encode($data);
		$viewer->assign('DATA_JSON', \App\Modules\Base\Helpers\Util::toSafeHTML($dataJson));
		
		// Prepare roles list
		$viewer->assign('ALL_ROLES', \App\Modules\Settings\Roles\Models\Record::getAll());
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
			'~libraries/jquery/jstree/jstree.min.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();
		$cssFileNames = [
			'~libraries/jquery/jstree/themes/default/style.css',
			"modules.Settings.$moduleName.Index",
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}
}
