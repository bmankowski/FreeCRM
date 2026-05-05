<?php

namespace App\Modules\Settings\CronTasks\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class EditAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = \App\Modules\Settings\CronTasks\Models\Record::getInstanceById($recordId, $qualifiedModuleName);
		if (!$recordModel) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		$viewer = $this->getViewer($request);

		$moduleList = \App\Modules\Settings\Workflows\Models\Module::getSupportedModules();
		$currentModule = $recordModel->get('module');
		if ($currentModule !== null && $currentModule !== '') {
			$present = false;
			foreach ($moduleList as $moduleModel) {
				if ($moduleModel->getName() === $currentModule) {
					$present = true;
					break;
				}
			}
			if (!$present) {
				$fallback = new \stdClass();
				$fallback->name = $currentModule;
				$moduleList[] = $fallback;
			}
		}
		$viewer->assign('MODULE_LIST', $moduleList);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->view('EditAjax.tpl', $qualifiedModuleName);
	}
}
