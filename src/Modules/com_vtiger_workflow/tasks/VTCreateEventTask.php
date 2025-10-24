<?php

namespace App\Modules\com_vtiger_workflow\tasks;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */


class VTCreateEventTask extends \App\Modules\com_vtiger_workflow\VTTask
{

	public $executeImmediately = true;
	private $originalUser=null;
	private $startDays=null;
	private $startDirection=null;
	private $startDatefield=null;
	private $endDays=null;
	private $endDirection=null;
	private $endDatefield=null;
	private $assigned_user_id=null;
	private $eventType=null;
	private $eventName=null;
	private $description=null;
	private $sendNotification=null;
	private $startTime=null;
	private $endTime=null;
	private $status=null;
	private $priority=null;

	public function getFieldNames()
	{
		return array('eventType', 'eventName', 'description', 'sendNotification',
			'startTime', 'startDays', 'startDirection', 'startDatefield',
			'endTime', 'endDays', 'endDirection', 'endDatefield',
			'status', 'priority', 'assigned_user_id');
	}

	function getAdmin()
	{
		$user = \App\Modules\Users\Users::getActiveAdminUser();
		$currentUser = \App\User\CurrentUser::get();
		$this->originalUser = $currentUser;
		$currentUser = $user;
		return $user;
	}

	/**
	 * Execute task
	 * @param \App\Modules\Vtiger\Models\Record $recordModel
	 */
	public function doTask($recordModel)
	{
		if (!\App\Module::isModuleActive('Calendar')) {
			return;
		}
		$currentUser = \App\User\CurrentUser::get();
		$userId = $recordModel->get('assigned_user_id');
		$adminUser = $this->getAdmin();
		if ($userId === null) {
			$userId = $adminUser;
		}
		$moduleName = $recordModel->getModuleName();


		$startDate = $this->calculateDate($recordModel, $this->startDays, $this->startDirection, $this->startDatefield);
		$endDate = $this->calculateDate($recordModel, $this->endDays, $this->endDirection, $this->endDatefield);

		if ($this->assigned_user_id === 'currentUser') {
			$userId = \App\Modules\Users\Models\Record::getCurrentUserId();
		} else if ($this->assigned_user_id === 'triggerUser') {
			$userId = $recordModel->get('executeUser');
		} else if ($this->assigned_user_id === 'copyParentOwner') {
			$userId = $recordModel->get('assigned_user_id');
		} else if (!empty($this->assigned_user_id)) { // Added to check if the user/group is active
			$userExists = (new \App\Db\Query())->from('vtiger_users')
				->where(['id' => $this->assigned_user_id, 'status' => 'Active'])
				->exists();
			if ($userExists) {
				$userId = $this->assigned_user_id;
			} else {
				$groupExist = (new \App\Db\Query())->from('vtiger_groups')
					->where(['groupid' => $this->assigned_user_id])
					->exists();
				if ($groupExist) {
					$userId = $this->assigned_user_id;
				}
			}
		}
		$textParser = \App\TextParser::getInstanceByModel($recordModel);
		$fields = array(
			'activitytype' => $this->eventType,
			'description' => $textParser->setContent($this->description)->parse()->getContent(),
			'subject' => $textParser->setContent($this->eventName)->parse()->getContent(),
			'taskpriority' => $this->priority,
			'activitystatus' => $this->status,
			'assigned_user_id' => $userId,
			'time_start' => self::convertToDBFormat($this->startTime),
			'date_start' => $startDate,
			'time_end' => self::convertToDBFormat($this->endTime),
			'due_date' => $endDate,
			'duration_hours' => 0
		);
		$id = $recordModel->getId();
		$field = \App\ModuleHierarchy::getMappingRelatedField($moduleName);
		if ($field) {
			$fields[$field] = $id;
		}
		$newRecordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance('Events');
		$newRecordModel->setData($fields);
		$newRecordModel->setHandlerExceptions(['disableWorkflow' => true]);
		$newRecordModel->save();
		\App\Utils\Utils::relateEntities($recordModel->getEntity(), $moduleName, $recordModel->getId(), 'Calendar', $newRecordModel->getId());
		$currentUser = \App\User\CurrentUser::get();
		$currentUser = $this->originalUser;
	}

	private function calculateDate($recordModel, $days, $direction, $datefield)
	{
		$baseDate = $recordModel->get($datefield);
		if ($baseDate == '') {
			$baseDate = date('Y-m-d');
		}
		if ($days == '') {
			$days = 0;
		}
		preg_match('/\d\d\d\d-\d\d-\d\d/', $baseDate, $match);
		$baseDate = strtotime($match[0]);
		$date = strftime('%Y-%m-%d', $baseDate + $days * 24 * 60 * 60 *
			(strtolower($direction) == 'before' ? -1 : 1));
		return $date;
	}

	/**
	 * To convert time_start & time_end values to db format
	 * @param string $timeStr
	 * @return string
	 */
	public static function convertToDBFormat($timeStr): string
	{
		$date = new \DateTime();
		$time = \App\Modules\Vtiger\UiTypes\Time::getTimeValueWithSeconds($timeStr);
		$dbInsertDateTime = \App\Fields\DateTimeField::convertToDBTimeZone($date->format('Y-m-d') . ' ' . $time);
		return $dbInsertDateTime->format('H:i:s');
	}

	static function conv12to24hour($timeStr)
	{
		$arr = array();
		preg_match('/(\d{1,2}):(\d{1,2})(am|pm)/', $timeStr, $arr);
		if ($arr[3] == 'am') {
			$hours = ((int) $arr[1]) % 12;
		} else {
			$hours = ((int) $arr[1]) % 12 + 12;
		}
		return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($arr[2], 2, '0', STR_PAD_LEFT);
	}

	public function getTimeFieldList()
	{
		return array('startTime', 'endTime');
	}
}
