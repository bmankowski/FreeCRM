<?php

namespace App\Modules\Calendar\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Delete extends \App\Runtime\Vtiger_Action_Controller
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$ajaxDelete = $request->get('ajaxDelete');

	$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName);
	$moduleModel = $recordModel->getModule();
	$recordModel->delete();

	$typeRemove = \App\Modules\Events\Models\RecuringEvents::UPDATE_THIS_EVENT;
	if (!$request->isEmpty('typeRemove')) {
		$typeRemove = $request->get('typeRemove');
	}
	$recurringEvents = \App\Modules\Events\Models\RecuringEvents::getInstance();
		$recurringEvents->typeSaving = $typeRemove;
		$recurringEvents->recordModel = $recordModel;
		$recurringEvents->templateRecordId = $recordId;
		$recurringEvents->delete();
		$listViewUrl = $moduleModel->getListViewUrl();
		if ($ajaxDelete) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult($listViewUrl);
			return $response;
		} else {
			header("Location: $listViewUrl");
		}
	}
}
