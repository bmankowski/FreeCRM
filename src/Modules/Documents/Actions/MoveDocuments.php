<?php

namespace FreeCRM\Modules\Documents\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com.
 * *********************************************************************************** */

class MoveDocuments extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();

		if (!\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'EditView')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$documentIdsList = $this->getRecordsListFromRequest($request);
		$folderId = $request->get('folderid');

		if (!empty($documentIdsList)) {
			foreach ($documentIdsList as $documentId) {
				$documentModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($documentId, $moduleName);
				if (\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'EditView', $documentId)) {
					$documentModel->set('folderid', $folderId);
					$documentModel->save();
				} else {
					$documentsMoveDenied[] = $documentModel->getName();
				}
			}
		}
		if (empty($documentsMoveDenied)) {
			$result = array('success' => true, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_DOCUMENTS_MOVED_SUCCESSFULLY', $moduleName));
		} else {
			$result = array('success' => false, 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_DENIED_DOCUMENTS', $moduleName), 'LBL_RECORDS_LIST' => $documentsMoveDenied);
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
