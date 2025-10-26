<?php

namespace App\Modules\Base\Actions;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class ProcessDuplicates extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$module = $request->getModule();
		$records = $request->get('records');
		if ($records) {
			foreach ($records as $record) {
				$recordPermission = \App\Modules\Users\Models\Privileges::isPermitted($module, 'EditView', $record);
				if (!$recordPermission) {
					throw new \App\Exceptions\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
				}
			}
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$records = $request->get('records');
		$primaryRecord = $request->get('primaryRecord');
		$primaryRecordModel = \App\Modules\Base\Models\Record::getInstanceById($primaryRecord, $moduleName);

		$fields = $moduleModel->getFields();
		foreach ($fields as $field) {
			$fieldValue = $request->get($field->getName());
			if ($field->isEditable()) {
				$primaryRecordModel->set($field->getName(), $fieldValue);
			}
		}
		$primaryRecordModel->save();

		$deleteRecords = array_diff($records, array($primaryRecord));
		foreach ($deleteRecords as $deleteRecord) {
			$record = \App\Modules\Base\Models\Record::getInstanceById($deleteRecord, $moduleName);
			if ($record->isDeletable()) {
				$primaryRecordModel->transferRelationInfoOfRecords([$deleteRecord]);
				$record->delete();
			}
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
