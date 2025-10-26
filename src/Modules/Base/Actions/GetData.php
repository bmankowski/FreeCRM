<?php

namespace App\Modules\Base\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use App\Http\Vtiger_Request;

class GetData  extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('source_module');
		$recordId = $request->get('record');

		$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($sourceModule, 'DetailView', $recordId);
		if (!$recordPermission) {
			throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
		}
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$sourceModule = $request->get('source_module');
		$response = new \App\Http\Vtiger_Response();

		$permitted = \App\Modules\Users\Models\Privileges::isPermitted($sourceModule, 'DetailView', $record);
		if ($permitted) {
			vglobal('showsAdditionalLabels', true);
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($record, $sourceModule);
			$data = $recordModel->getData();
			$response->setResult(array('success' => true, 'data' => array_map('decode_html', $data)));
		} else {
			$response->setResult(array('success' => false, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED')));
		}
		$response->emit();
	}
}
