<?php

namespace App\Modules\Settings\GlobalPermission\Actions;
use App\Modules\Settings\Vtiger\Models\Tracker;
use App\Modules\Settings\GlobalPermissionModels\Record;


/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */

class Save extends \App\Modules\Settings\Vtiger\Actions\Save
{

	public function __construct()
	{
		parent::__construct();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		if (!$currentUser->isAdminUser()) {
			throw new \Exception\AppException('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		// Initialize tracker with request parameter instead of AppRequest
		\App\Modules\Settings\Vtiger\Models\Tracker::setRecordId($request->get('profileID'));
		$profileID = $request->get('profileID');
		$checked = $request->get('checked');
		$globalactionid = $request->get('globalactionid');
		if ($globalactionid == 1) {
			$globalActionName = 'LBL_VIEW_ALL';
		} else {
			$globalActionName = 'LBL_EDIT_ALL';
		}
		if ($checked == 'true') {
			$checked = 1;
			$prev[$globalActionName] = 0;
		} else {
			$checked = 0;
			$prev[$globalActionName] = 1;
		}
		$post[$globalActionName] = $checked;
		\App\Modules\Settings\GlobalPermission\Models\Record::save($profileID, $globalactionid, $checked);
		\App\Modules\Settings\Vtiger\Models\Tracker::addDetail($prev, $post);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_SAVE_OK', $request->getModule(false))));
		$response->emit();
	}
}
