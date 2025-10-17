<?php

namespace FreeCRM\Modules\OSSMailScanner\Actions;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class SaveActions extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$currentUserModel = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!$currentUserModel->isAdminUser()) {
			throw new \Exception\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$userid = $request->get('userid');
		$vale = $request->get('vale');
		if ($userid) {
			if ($vale != 'null') {
				$vale = implode(',', $vale);
			}
			$OSSMailScannerModel = \FreeCRM\Modules\Vtiger\Models\Record::getCleanInstance('OSSMailScanner');
			$OSSMailScannerModel->setActions($userid, $vale);
			$success = true;
			$data = \FreeCRM\Runtime\Vtiger_Language_Handler::translate('JS_save_info', 'OSSMailScanner');
		} else {
			$success = false;
			$data = 'Error: Brak userid';
		}
		$result = array('success' => $success, 'data' => $data);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
