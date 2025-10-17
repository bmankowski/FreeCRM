<?php

namespace App\Modules\Settings\Groups\Actions;
use App\Modules\Settings\Vtiger\Models\Tracker;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteAjax extends \App\Modules\Settings\Vtiger\Actions\Delete
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$transferRecordId = $request->get('transfer_record');

		$recordModel = Settings_Groups_Record_Model::getInstance($recordId);
		$members = $recordModel->getMembers();
		$membersToDipslay = [];
		foreach ($members as $typeMembers) {
			foreach ($typeMembers as $member) {
				$membersToDipslay[] = $member->get('id');
			}
		}
		$recordModel->set('group_members', $membersToDipslay);
		$recordModel->set('modules', $recordModel->getModules());
		$prevValues = $recordModel->getDisplayData();
		$transferToOwner = Settings_Groups_Record_Model::getInstance($transferRecordId);
		if (!$transferToOwner) {
			$transferToOwner = \App\Modules\Users\Models\Record::getInstanceById($transferRecordId, 'Users');
		}

		if ($recordModel && $transferToOwner) {
			\App\Modules\Settings\Vtiger\Models\Tracker::addDetail([], $prevValues);
			$recordModel->delete($transferToOwner);
		}

		$response = new \App\Http\Vtiger_Response();
		$result = array('success' => true);

		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
