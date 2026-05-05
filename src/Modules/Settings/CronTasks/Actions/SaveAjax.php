<?php

namespace App\Modules\Settings\CronTasks\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class SaveAjax extends \App\Modules\Settings\Base\Actions\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		parent::checkPermission($request);

		$recordId = $request->get('record');
		if (!$recordId) {
			throw new \App\Exceptions\AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = \App\Modules\Settings\CronTasks\Models\Record::getInstanceById($recordId, $qualifiedModuleName);

		$fieldsList = $recordModel->getModule()->getEditableFieldsList();
		foreach ($fieldsList as $fieldName) {
			$fieldValue = $request->get($fieldName);
			if (isset($fieldValue)) {
				$recordModel->set($fieldName, $fieldValue);
			}
		}

		$cronModule = $request->get('cron_module');
		if ($cronModule !== null) {
			$recordModel->set('module', $cronModule);
		}

		$recordModel->save();

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array(true));
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
