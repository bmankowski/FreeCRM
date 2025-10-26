<?php

namespace App\Modules\OSSMailScanner\Actions;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class restartCron extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = $request->getUser();
		if (!$currentUserModel->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$param = $request->get('updatedFields');
		$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance('OSSMailScanner');
		$recordModel->runRestartCron();
		$recordModel->verificationCron();
		$result = array('success' => true, 'data' => \App\Runtime\Vtiger_Language_Handler::translate('JS_info_restart_ok', 'OSSMailScanner'));
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
