<?php

namespace App\Modules\Settings\Picklist\Actions;
use App\Modules\Settings\PicklistModels\Field;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class SaveAjax extends \App\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		$this->exposeMethod('add');
		$this->exposeMethod('rename');
		$this->exposeMethod('remove');
		$this->exposeMethod('assignValueToRole');
		$this->exposeMethod('saveOrder');
		$this->exposeMethod('enableOrDisable');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		$this->invokeExposedMethod($mode, $request);
	}
	/*
	 * @function updates user tables with new picklist value for default event and status fields
	 */

	public function updateDefaultPicklistValues($pickListFieldName, $oldValue, $newValue)
	{
		$db = \App\Database\PearDatabase::getInstance();
		if ($pickListFieldName == 'activitytype')
			$defaultFieldName = 'defaultactivitytype';
		else
			$defaultFieldName = 'defaulteventstatus';
		$queryToGetId = sprintf('SELECT id FROM vtiger_users WHERE %s IN (', $defaultFieldName);
		if (is_array($oldValue)) {
			$countOldValue = count($oldValue);
			for ($i = 0; $i < $countOldValue; $i++) {
				$queryToGetId .= '"' . $oldValue[$i] . '"';
				if ($i < (count($oldValue) - 1)) {
					$queryToGetId .= ',';
				}
			}
			$queryToGetId .= ')';
		} else {
			$queryToGetId .= '"' . $oldValue . '")';
		}
		$result = $db->pquery($queryToGetId, []);
		$rowCount = $db->num_rows($result);
		for ($i = 0; $i < $rowCount; $i++) {
			$recordId = $db->query_result_rowdata($result, $i);
			$recordId = $recordId['id'];
			$record = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId, 'Users');
			$record->set($defaultFieldName, $newValue);
			$record->save();
		}
	}

	public function add(\App\Http\Vtiger_Request $request)
	{
		$newValue = $request->getRaw('newValue');
		$pickListName = $request->get('picklistName');
		$moduleName = $request->get('source_module');
		$moduleModel = Settings_Picklist_Module_Model::getInstance($moduleName);
		$fieldModel = \App\Modules\Settings\Picklist\Models\Field::getInstance($pickListName, $moduleModel);
		$rolesSelected = [];
		if ($fieldModel->isRoleBased()) {
			$userSelectedRoles = $request->get('rolesSelected', []);
			//selected all roles option
			if (in_array('all', $userSelectedRoles)) {
				$roleRecordList = \App\Modules\Settings\Roles\Models\Record::getAll();
				foreach ($roleRecordList as $roleRecord) {
					$rolesSelected[] = $roleRecord->getId();
				}
			} else {
				$rolesSelected = $userSelectedRoles;
			}
		}
		$response = new \App\Http\Vtiger_Response();
		try {
			$id = $moduleModel->addPickListValues($fieldModel, $newValue, $rolesSelected);
			$response->setResult(array('id' => $id['id']));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function rename(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->get('source_module');

		$newValue = $request->getRaw('newValue');
		$pickListFieldName = $request->get('picklistName');
		$oldValue = $request->getRaw('oldValue');
		$id = $request->getRaw('id');

		if ($moduleName == 'Events' && ($pickListFieldName == 'activitytype' || $pickListFieldName == 'activitystatus')) {
			$this->updateDefaultPicklistValues($pickListFieldName, $oldValue, $newValue);
		}
		$moduleModel = new Settings_Picklist_Module_Model();
		$response = new \App\Http\Vtiger_Response();
		try {
			$status = $moduleModel->renamePickListValues($pickListFieldName, $oldValue, $newValue, $moduleName, $id);
			$response->setResult(array('success', $status));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function remove(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->get('source_module');
		$valueToDelete = $request->getRaw('delete_value');
		$replaceValue = $request->getRaw('replace_value');
		$pickListFieldName = $request->get('picklistName');

		if ($moduleName == 'Events' && ($pickListFieldName == 'activitytype' || $pickListFieldName == 'activitystatus')) {
			$this->updateDefaultPicklistValues($pickListFieldName, $valueToDelete, $replaceValue);
		}
		$moduleModel = Settings_Picklist_Module_Model::getInstance($moduleName);
		$response = new \App\Http\Vtiger_Response();
		try {
			$status = $moduleModel->remove($pickListFieldName, $valueToDelete, $replaceValue, $moduleName);
			$response->setResult(array('success', $status));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	/**
	 * Function which will assign existing values to the roles
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function assignValueToRole(\App\Http\Vtiger_Request $request)
	{
		$valueToAssign = $request->getRaw('assign_values');
		$userSelectedRoles = $request->get('rolesSelected');

		$roleIdList = [];
		//selected all roles option
		if (in_array('all', $userSelectedRoles)) {
			$roleRecordList = \App\Modules\Settings\Roles\Models\Record::getAll();
			foreach ($roleRecordList as $roleRecord) {
				$roleIdList[] = $roleRecord->getId();
			}
		} else {
			$roleIdList = $userSelectedRoles;
		}

		$moduleModel = new Settings_Picklist_Module_Model();

		$response = new \App\Http\Vtiger_Response();
		try {
			$moduleModel->enableOrDisableValuesForRole($request->getForSql('picklistName'), $valueToAssign, [], $roleIdList);
			$response->setResult(array('success', true));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function saveOrder(\App\Http\Vtiger_Request $request)
	{
		$picklistValues = $request->getRaw('picklistValues');

		$moduleModel = new Settings_Picklist_Module_Model();
		$response = new \App\Http\Vtiger_Response();
		try {
			$moduleModel->updateSequence($request->getForSql('picklistName'), $picklistValues);
			$response->setResult(array('success', true));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function enableOrDisable(\App\Http\Vtiger_Request $request)
	{
		$enabledValues = $request->getRaw('enabled_values', []);
		$disabledValues = $request->getRaw('disabled_values', []);
		$roleSelected = $request->get('rolesSelected');

		$moduleModel = new Settings_Picklist_Module_Model();
		$response = new \App\Http\Vtiger_Response();
		try {
			$moduleModel->enableOrDisableValuesForRole($request->getForSql('picklistName'), $enabledValues, $disabledValues, array($roleSelected));
			$response->setResult(array('success', true));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
