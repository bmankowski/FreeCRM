<?php

namespace FreeCRM\Modules\Documents\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class CheckFileIntegrity extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		if (!\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $request->get('record'))) {
			throw new \Exception\NoPermittedToRecord(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED', $moduleName));
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$documentRecordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName);
		$resultVal = $documentRecordModel->checkFileIntegrity();

		$result = array('success' => $resultVal);
		if ($resultVal) {
			$documentRecordModel->updateFileStatus(1);
			$result['message'] = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FILE_AVAILABLE', $moduleName);
		} else {
			$documentRecordModel->updateFileStatus(0);
			$result['message'] = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_FILE_NOT_AVAILABLE', $moduleName);
		}
		$result['url'] = $documentRecordModel->getDetailViewUrl();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
