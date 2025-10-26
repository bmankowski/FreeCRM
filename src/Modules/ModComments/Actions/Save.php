<?php

namespace App\Modules\ModComments\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Save extends \App\Runtime\BaseActionController
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$request->set('assigned_user_id', \App\Modules\Users\Models\Record::getCurrentUserId());
		$recordModel = $this->saveRecord($request);
		$responseFieldsToSent = array('reasontoedit', 'commentcontent');
		$fieldModelList = $recordModel->getModule()->getFields();
		foreach ($responseFieldsToSent as &$fieldName) {
			$fieldModel = $fieldModelList[$fieldName];
			$fieldValue = $recordModel->get($fieldName);
			$result[$fieldName] = \App\Modules\Base\Helpers\Util::toSafeHTML($fieldModel->getDisplayValue($fieldValue));
		}

		$result['success'] = true;
		$result['modifiedtime'] = \App\Modules\Base\Helpers\Util::formatDateDiffInStrings($recordModel->get('modifiedtime'));
		$result['modifiedtimetitle'] = \App\Modules\Base\Helpers\Util::formatDateTimeIntoDayString($recordModel->get('modifiedtime'));

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}
}
