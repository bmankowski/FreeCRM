<?php

namespace FreeCRM\Modules\Settings\Groups\Actions;
use FreeCRM\Modules\Settings\Vtiger\Models\Tracker;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use FreeCRM\Modules\Settings\Groups\Models\Record as Settings_Groups_Record_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Delete
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
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
			$transferToOwner = \FreeCRM\Modules\Users\Models\Record::getInstanceById($transferRecordId, 'Users');
		}

		if ($recordModel && $transferToOwner) {
			\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::addDetail([], $prevValues);
			$recordModel->delete($transferToOwner);
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$result = array('success' => true);

		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
