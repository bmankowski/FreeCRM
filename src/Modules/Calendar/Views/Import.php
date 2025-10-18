<?php

namespace App\Modules\Calendar\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


class Import extends \Vtiger_Index_View
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('import');
		$this->exposeMethod('importResult');
		$this->exposeMethod('undoImport');
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			parent::preProcess($request);
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		echo $this->import($request);
	}

	public function postprocess(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			parent::postProcess($request);
		}
	}

	/**
	 * Function to show import UI in Calendar Module
	 * @param Vtiger_Request $request
	 */
	public function import(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SUPPORTED_FILE_TYPES', Import_Utils_Helper::getSupportedFileExtensions($moduleName));
		$viewer->assign('SUPPORTED_FILE_TYPES_TEXT', Import_Utils_Helper::getSupportedFileExtensionsDescription($moduleName));
		$viewer->view('Import.tpl', $moduleName);
	}

	/**
	 * Function to show result of import
	 * @param Vtiger_Request $request
	 */
	public function importResult(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$userId = $currentUserModel->getId();
		$moduleName = $request->getModule();
		$viewer = $this->getViewer($request);
		$request->set('type', 'ics');

		if (Import_Utils_Helper::validateFileUpload($request)) {
			$lastImport = new iCalLastImport();
			$lastImport->clearRecords($userId);

			$eventModule = 'Events';
			$todoModule = 'Calendar';

			$skipFields = array(
				$eventModule => array('duration_hours'),
				$todoModule => array('activitystatus')
			);

			$requiredFields = array();
			$modules = array($eventModule, $todoModule);
			$calendarModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);

			foreach ($modules as $module) {
				$moduleRequiredFields = array_keys($calendarModel->getRequiredFields($module));
				$requiredFields[$module] = array_diff($moduleRequiredFields, $skipFields[$module]);
				$totalCount[$module] = 0;
				$skipCount[$module] = 0;
			}

			$ical = new iCal();
			$icalActivities = $ical->iCalReader("IMPORT_" . $userId);
			$noOfActivities = count($icalActivities);

			for ($i = 0; $i < $noOfActivities; $i++) {
				if ($icalActivities[$i]['TYPE'] == 'VEVENT') {
					$activity = new iCalendar_event;
					$module = $eventModule;
				} else {
					$activity = new iCalendar_todo;
					$module = $todoModule;
				}

				$totalCount[$module] ++;
				$activityFieldsList = $activity->generateArray($icalActivities[$i]);
				if (!array_key_exists('visibility', $activityFieldsList)) {
					$activityFieldsList['visibility'] = ' ';
				}
				if (array_key_exists('taskpriority', $activityFieldsList)) {
					$priorityMap = array('0' => 'Medium', '1' => 'High', '2' => 'Medium', '3' => 'Low');
					$priorityval = $activityFieldsList['taskpriority'];
					if (array_key_exists($priorityval, $priorityMap))
						$activityFieldsList['taskpriority'] = $priorityMap[$priorityval];
				}

				$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($moduleName);
				$recordModel->setData($activityFieldsList);
				$recordModel->set('assigned_user_id', $userId);

				$skipRecord = false;
				foreach ($requiredFields[$module] as $key) {
					$value = $recordModel->get($key);
					if (empty($value)) {
						$skipCount[$module] ++;
						$skipRecord = true;
						break;
					}
				}
				if ($skipRecord === true) {
					continue;
				}
				$recordModel->save();

				$lastImport = new iCalLastImport();
				$lastImport->setFields(array('userid' => $userId, 'entitytype' => $todoModule, 'crmid' => $recordModel->getId()));
				$lastImport->save();

				if (!empty($icalActivities[$i]['VALARM'])) {
					$recordModel->setActivityReminder(0, '', '');
				}
			}

			$importedEvents = $totalCount[$eventModule] - $skipCount[$eventModule];
			$importedTasks = $totalCount[$todoModule] - $skipCount[$todoModule];

			$viewer->assign('SUCCESS_EVENTS', $importedEvents);
			$viewer->assign('SKIPPED_EVENTS', $skipCount[$eventModule]);
			$viewer->assign('SUCCESS_TASKS', $importedTasks);
			$viewer->assign('SKIPPED_TASKS', $skipCount[$todoModule]);
		} else {
			$viewer->assign('ERROR_MESSAGE', $request->get('error_message'));
		}

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('VIEW', 'List');

		$viewer->view('ImportResult.tpl', $moduleName);
	}

	/**
	 * Function to show result of undo import
	 * @param Vtiger_Request $request
	 */
	public function undoImport(\App\Http\Vtiger_Request $request)
	{
		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$moduleName = $request->getModule();

		$lastImport = new iCalLastImport();
		$returnValue = $lastImport->undo($moduleName, $currentUserModel->getId());
		if (!empty($returnValue)) {
			$undoStatus = true;
		} else {
			$undoStatus = false;
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('VIEW', 'List');
		$viewer->assign('UNDO_STATUS', $undoStatus);

		$viewer->view('ImportFinalResult.tpl', $moduleName);
	}
}
