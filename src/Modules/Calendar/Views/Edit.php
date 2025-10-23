<?php

namespace App\Modules\Calendar\Views;
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Edit extends \App\Modules\Vtiger\Views\Edit
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('Events');
		$this->exposeMethod('Calendar');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();

		$recordId = $request->get('record');
		if (!empty($recordId)) {
			$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId);
			$mode = $recordModel->getType();
		}

		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request, $mode);
			return;
		}
		$this->Calendar($request, 'Calendar');
	}

	public function Events($request, $moduleName)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();

		$viewer = $this->getViewer($request);
		$record = $request->get('record');

		if (!empty($record) && $request->getBoolean('isDuplicate') === true) {
			$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', '');
		} else if (!empty($record)) {
			$recordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($record, $moduleName);
			$viewer->assign('MODE', 'edit');
			$viewer->assign('RECORD_ID', $record);
		} else {
			$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
			$viewer->assign('MODE', '');
		}
		$eventModule = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$recordModel->setModuleFromInstance($eventModule);

		$moduleModel = $recordModel->getModule();
		$fieldList = $moduleModel->getFields();
		$requestFieldList = array_intersect_key($request->getAll(), $fieldList);

		foreach ($requestFieldList as $fieldName => $fieldValue) {
			$fieldModel = $fieldList[$fieldName];
			$specialField = false;
			// We collate date and time part together in the EditView UI handling 
			// so a bit of special treatment is required if we come from QuickCreate 
			if (empty($record) && ($fieldName == 'time_start' || $fieldName == 'time_end') && !empty($fieldValue)) {
				$specialField = true;
				// Convert the incoming user-picked time to GMT time 
				// which will get re-translated based on user-time zone on EditForm 
				$fieldValue = \App\Fields\DateTimeField::convertToDBTimeZone($fieldValue)->format("H:i");
			}
			if (empty($record) && ($fieldName == 'date_start' || $fieldName == 'due_date') && !empty($fieldValue)) {
				if ($fieldName == 'date_start') {
					$startTime = \App\Modules\Vtiger\UiTypes\Time::getTimeValueWithSeconds($requestFieldList['time_start']);
					$startDateTime = \App\Modules\Vtiger\UiTypes\Datetime::getDBDateTimeValue($fieldValue . " " . $startTime);
					list($startDate, $startTime) = explode(' ', $startDateTime);
					$fieldValue = \App\Modules\Vtiger\UiTypes\Date::getDisplayDateValue($startDate);
				} else {
					$endTime = \App\Modules\Vtiger\UiTypes\Time::getTimeValueWithSeconds($requestFieldList['time_end']);
					$endDateTime = \App\Modules\Vtiger\UiTypes\Datetime::getDBDateTimeValue($fieldValue . " " . $endTime);
					list($endDate, $endTime) = explode(' ', $endDateTime);
					$fieldValue = \App\Modules\Vtiger\UiTypes\Date::getDisplayDateValue($endDate);
				}
			}

			if ($fieldModel->isEditable() || $specialField) {
				$recordModel->set($fieldName, $fieldModel->getDBValue($fieldValue));
			}
		}
		$recordStructureInstance = \App\Modules\Vtiger\Models\RecordStructure::getInstanceFromRecordModel($recordModel, \App\Modules\Vtiger\Models\RecordStructure::RECORD_STRUCTURE_MODE_EDIT);
		$recordStructure = $recordStructureInstance->getStructure();

		$viewMode = $request->get('view_mode');
		if (!empty($viewMode)) {
			$viewer->assign('VIEW_MODE', $viewMode);
		}

		$userChangedEndDateTime = $request->get('userChangedEndDateTime');
		$isRelationOperation = $request->get('relationOperation');
		//if it is relation edit
		$viewer->assign('IS_RELATION_OPERATION', $isRelationOperation);
		if ($isRelationOperation) {
			$sourceModule = $request->get('sourceModule');
			$sourceRecord = $request->get('sourceRecord');

			$viewer->assign('SOURCE_MODULE', $sourceModule);
			$viewer->assign('SOURCE_RECORD', $sourceRecord);
			$sourceRelatedField = $moduleModel->getValuesFromSource($request, $moduleName);
			foreach ($recordStructure as &$block) {
				foreach ($sourceRelatedField as $field => &$value) {
					if (isset($block[$field])) {
						$fieldvalue = $block[$field]->get('fieldvalue');
						if (empty($fieldvalue)) {
							$block[$field]->set('fieldvalue', $value);
						}
					}
				}
			}
		}
		$viewer->assign('USER_CHANGED_END_DATE_TIME', $userChangedEndDateTime);
		$viewer->assign('TOMORROWDATE', \App\Modules\Vtiger\UiTypes\Date::getDisplayDateValue(date('Y-m-d', time() + 86400)));
		$viewer->assign('RECORD_STRUCTURE_MODEL', $recordStructureInstance);
		$viewer->assign('RECORD_STRUCTURE', $recordStructure);
		$viewer->assign('RECORD', $recordModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('BLOCK_LIST', $moduleModel->getBlocks());
		$viewer->assign('CURRENTDATE', date('Y-n-j'));
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());
		$viewer->assign('PICKIST_DEPENDENCY_DATASOURCE', \App\Json::encode(\App\Modules\PickList\DependencyPicklist::getPicklistDependencyDatasource($moduleName)));
		$viewer->assign('MAPPING_RELATED_FIELD', \App\Json::encode(\App\ModuleHierarchy::getRelationFieldByHierarchy($moduleName)));
		$viewer->assign('INVITIES_SELECTED', $recordModel->getInvities());
		$viewer->assign('CURRENT_USER', $currentUser);

		$viewer->view('EditView.tpl', $moduleName);
	}

	public function Calendar($request, $moduleName)
	{
		parent::process($request);
	}
}
