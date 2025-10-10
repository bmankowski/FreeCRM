<?php

namespace FreeCRM\Modules\ModComments\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Save extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->set('assigned_user_id', \App\User::getCurrentUserId());
		$recordModel = $this->saveRecord($request);
		$responseFieldsToSent = array('reasontoedit', 'commentcontent');
		$fieldModelList = $recordModel->getModule()->getFields();
		foreach ($responseFieldsToSent as &$fieldName) {
			$fieldModel = $fieldModelList[$fieldName];
			$fieldValue = $recordModel->get($fieldName);
			$result[$fieldName] = \Vtiger_Util_Helper::toSafeHTML($fieldModel->getDisplayValue($fieldValue));
		}

		$result['success'] = true;
		$result['modifiedtime'] = \Vtiger_Util_Helper::formatDateDiffInStrings($recordModel->get('modifiedtime'));
		$result['modifiedtimetitle'] = \Vtiger_Util_Helper::formatDateTimeIntoDayString($recordModel->get('modifiedtime'));

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setEmitType(\FreeCRM\Http\Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}
}
