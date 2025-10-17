<?php

namespace FreeCRM\Modules\Users\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteImage extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('id');

		if (!(\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'EditView', $record) && \FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Delete', $record))) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$imageId = $request->get('imageid');

		$response = new \FreeCRM\Http\Vtiger_Response();
		if ($recordId) {
			$recordModel = \FreeCRM\Modules\Users\Models\Record::getInstanceById($recordId, $moduleName);
			$status = $recordModel->deleteImage($imageId);
			if ($status) {
				$response->setResult(array(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_IMAGE_DELETED_SUCCESSFULLY', $moduleName)));
			}
		} else {
			$response->setError(\FreeCRM\Runtime\Vtiger_Language_Handler::translate('LBL_IMAGE_NOT_DELETED', $moduleName));
		}

		$response->emit();
	}
}
