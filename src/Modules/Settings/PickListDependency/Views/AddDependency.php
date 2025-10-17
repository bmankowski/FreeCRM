<?php

namespace App\Modules\Settings\PickListDependency\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class AddDependency extends \App\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('GetPickListFields');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode) && method_exists($this, $mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}

		$qualifiedModule = $request->getModule(true);
		$viewer = $this->getViewer($request);
		$moduleModels = \App\Modules\Vtiger\Models\Module::getEntityModules();

		$viewer->assign('MODULES', $moduleModels);
		echo $viewer->view('AddDependency.tpl', $qualifiedModule);
	}

	/**
	 * Function returns the picklist field for a module
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function GetPickListFields(\App\Http\Vtiger_Request $request)
	{
		$module = $request->get('sourceModule');

		$fieldList = Settings_PickListDependency_Module_Model::getAvailablePicklists($module);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($fieldList);
		$response->emit();
	}

	public function CheckCyclicDependency()
	{
		
	}
}
