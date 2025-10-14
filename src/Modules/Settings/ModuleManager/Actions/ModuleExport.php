<?php

namespace FreeCRM\Modules\Settings\ModuleManager\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

Class Settings_ModuleManager_ModuleExport_Action extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('exportModule');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	protected function exportModule(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->get('forModule');

		$moduleModel = \Vtiger_Module_Model::getInstance($moduleName);

		if (!$moduleModel->isExportable()) {
			echo 'Module not exportable!';
			return;
		}

		$package = new vtlib\PackageExport();
		$package->export($moduleModel, '', sprintf("%s-%s.zip", $moduleModel->get('name'), $moduleModel->get('version')), true);
	}
}
