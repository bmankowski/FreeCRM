<?php

namespace App\Modules\Users\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteImage extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('id');

		if (!(\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'EditView', $record) && \App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'Delete', $record))) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$imageId = $request->get('imageid');

		$response = new \App\Http\Vtiger_Response();
		if ($recordId) {
			$recordModel = \App\Modules\Users\Models\Record::getInstanceById($recordId, $moduleName);
			$status = $recordModel->deleteImage($imageId);
			if ($status) {
				$response->setResult(array(\App\Runtime\Vtiger_Language_Handler::translate('LBL_IMAGE_DELETED_SUCCESSFULLY', $moduleName)));
			}
		} else {
			$response->setError(\App\Runtime\Vtiger_Language_Handler::translate('LBL_IMAGE_NOT_DELETED', $moduleName));
		}

		$response->emit();
	}
}
