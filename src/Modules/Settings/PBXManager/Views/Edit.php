<?php

namespace App\Modules\Settings\PBXManager\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Edit extends \App\Modules\Settings\Base\Views\Index
{

	public function __construct()
	{
		$this->exposeMethod('showPopup');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->showPopup($request);
	}

	public function showPopup(\App\Http\Vtiger_Request $request)
	{
		$id = $request->get('id');
		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		if ($id) {
			$recordModel = Settings_PBXManager_Record_Model::getInstanceById($id, $qualifiedModuleName);
		} else {
			$recordModel = Settings_PBXManager_Record_Model::getCleanInstance();
		}
		$viewer->assign('RECORD_ID', $id);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $request->getModule(false));
		
		// Prepare PBXManager Edit-specific data for Edit template
		$this->preparePBXManagerEditData($viewer);
		
		$viewer->view('Edit.tpl', $request->getModule(false));
	}
	
	/**
	 * Prepare data for PBXManager Edit template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function preparePBXManagerEditData($viewer)
	{
		$viewer->assign('SETTINGS_PARAMETERS', \App\Modules\PBXManager\Connectors\PBXManager::getSettingsParameters());
		$viewer->assign('MODULE_MODEL', \App\Modules\Settings\PBXManager\Models\Module::getCleanInstance());
	}
}
