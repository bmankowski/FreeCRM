<?php

namespace App\Modules\CustomView\Models;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

/**
 * CustomView Record Model Class
 */
class Record extends \App\Modules\Base\Models\Record
{

	protected $isFeatured = false;
	protected $isDefault = false;
	protected $sortOrderBy = false;

	/**
	 * Function to get the Id
	 * @return mixed Custom View Id
	 */
	public function getId()
	{
		return $this->get('cvid');
	}

	/**
	 * Function to get the Owner Id
	 * @return mixed Id of the User who created the Custom View
	 */
	public function getOwnerId()
	{
		return $this->get('userid');
	}

	/**
	 * Function to get the Owner Name
	 * @return string Custom View creator User Name
	 */
	public function getOwnerName()
	{
		$ownerId = $this->getOwnerId();
		$entityNames = \App\Utils\Utils::getEntityName('Users', array($ownerId));
		return $entityNames[$ownerId];
	}

	/**
	 * Function to get the Module to which the record belongs
	 * @return \App\Modules\Base\Models\Module
	 */
	public function getModule()
	{
		return $this->module;
	}

	/**
	 * Function to set the Module to which the record belongs
	 * @param string $moduleName
	 * @return \App\Modules\Base\Models\Record or Module Specific Record Model instance
	 */
	public function setModule($moduleName)
	{
		$this->module = \App\Modules\Base\Models\Module::getInstance($moduleName);
		return $this;
	}

	/**
	 * Function to set the Module to which the record belongs from the Module model instance
	 * @param \App\Modules\Base\Models\Module $module
	 * @return \App\Modules\Base\Models\Record or Module Specific Record Model instance
	 */
	public function setModuleFromInstance($module)
	{
		$this->module = $module;
		return $this;
	}

	/**
	 * Function to check if the view is marked as default
	 * @return boolean true/false
	 */
	public function isDefault()
	{

		\App\Log\Log::trace('Entering ' . __METHOD__ . ' method ...');
		if ($this->isDefault === false) {
			$currentUser = \App\User\CurrentUser::get();
			$cvId = $this->getId();
			if (!$cvId) {
				$this->isDefault = false;
				return false;
			}
			$this->isDefault = (new \App\Db\Query())->from('vtiger_user_module_preferences')
				->where(['userid' => 'Users:' . $currentUser->getId(), 'tabid' => $this->getModule()->getId(), 'default_cvid' => $cvId])
				->exists();
		}
		\App\Log\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $this->isDefault;
	}

	public function isSystem()
	{
		return $this->get('status') == \App\View\CustomView::CV_STATUS_SYSTEM;
	}

	/**
	 * Function to check if the view is created by the current user or is default view
	 * @return boolean true/false
	 */
	public function isMine()
	{
		$userModel = \App\User\CurrentUser::get();
		return ($this->get('status') == \App\View\CustomView::CV_STATUS_DEFAULT || $this->get('userid') == $userModel->getId());
	}

	/**
	 * Function to check if the view is approved to be Public
	 * @return boolean true/false
	 */
	public function isPublic()
	{
		return (!$this->isMine() && $this->get('status') == \App\View\CustomView::CV_STATUS_PUBLIC);
	}

	/**
	 * Function to check if the view is marked as Private
	 * @return boolean true/false
	 */
	public function isPrivate()
	{
		return ($this->get('status') == \App\View\CustomView::CV_STATUS_PRIVATE);
	}

	/**
	 * Function to check if the view is requested to be Public and is awaiting for Approval
	 * @return boolean true/false
	 */
	public function isPending()
	{
		return (!$this->isMine() && $this->get('status') == \App\View\CustomView::CV_STATUS_PENDING);
	}

	/**
	 * Function to check if the view is created by one of the users, who is below the current user in the role hierarchy
	 * @return boolean true/false
	 */
	public function isOthers()
	{
		return (!$this->isMine() && $this->get('status') != \App\View\CustomView::CV_STATUS_PUBLIC);
	}

	/**
	 * Function which checks if a view is set to Public by the user which may/may not be approved.
	 * @return boolean true/false
	 */
	public function isSetPublic()
	{
		return ($this->get('status') == \App\View\CustomView::CV_STATUS_PUBLIC || $this->get('status') == \App\View\CustomView::CV_STATUS_PENDING);
	}

	public function isFeatured($editView = false)
	{

		\App\Log\Log::trace('Entering ' . __METHOD__ . ' method ...');
		if ($this->isFeatured === false) {
			if (empty($editView)) {
				if (!empty($this->get('featured'))) {
					$this->isFeatured = true;
				} else {
					$this->isFeatured = $this->checkPermissionToFeatured();
				}
			} else {
				$this->isFeatured = $this->checkFeaturedInEditView();
			}
		}
		\App\Log\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $this->isFeatured;
	}

	public function checkFeaturedInEditView()
	{
		$db = \App\Db\Db::getInstance('admin');
		$cvId = $this->getId();
		if (!$cvId)
			return false;
		return (new \App\Db\Query())->from('u_#__featured_filter')
				->where(['cvid' => $cvId, 'user' => 'Users:' . \App\User\CurrentUser::get()->getId()])
				->exists($db);
	}

	public function checkPermissionToFeatured($editView = false)
	{
		$currentUser = \App\User\CurrentUser::get();
		$query = (new \App\Db\Query())->from('u_#__featured_filter');
	if ($currentUser->isAdminUser()) {
		$userGroups = $currentUser->getUserGroups($currentUser->getId()) ?? [];
		$parentRoles = \App\Security\PrivilegeUtil::getRoleDetail($currentUser->getRole());
		$parentRoles = $parentRoles['parentrole'] ? $parentRoles['parentrole'] : '';
	} else {
		$parentRoles = $currentUser->getParentRoleSequence();
		$userGroups = $currentUser->get('privileges')->get('groups') ?? [];
	}
		$where = ['or', ['user' => 'Users:' . $currentUser->getId()], ['user' => 'Roles:' . $currentUser->getRole()]];
		foreach ($userGroups as $groupId) {
			$where [] = ['user' => "Groups:$groupId"];
		}
		if (!empty($parentRoles)) {
			foreach (explode('::', $parentRoles) as $role) {
				$where [] = ['user' => "RoleAndSubordinates:$role"];
			}
		}
		$query->where(['cvid' => $this->getId()]);
		$query->andWhere($where);
		return $query->exists();
	}

	public function isEditable()
	{
		if ($this->get('privileges') == 0) {
			return false;
		}
		$currentUser = \App\User\CurrentUser::get();
		if ($currentUser->isAdminUser()) {
			return true;
		}

		$moduleModel = $this->getModule();
		$moduleName = $moduleModel->get('name');
		if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CreateCustomFilter')) {
			return false;
		}

		if ($this->isMine() || $this->isOthers()) {
			return true;
		}
		return false;
	}

	public function isDeletable()
	{
		return $this->isEditable() && $this->get('presence') != 0;
	}

	/**
	 * Function which provides the records for the current view
	 * @param boolean $skipRecords - List of the RecordIds to be skipped
	 * @return int[] List of RecordsIds
	 */
	public function getRecordIds($skipRecords = false, $module = false, $lockRecords = false)
	{
		$cvId = $this->getId();
		$moduleModel = $this->getModule();
		$moduleName = $moduleModel->get('name');
		$baseTableName = $moduleModel->get('basetable');
		$baseTableId = $moduleModel->get('basetableid');
		$queryGenerator = new \App\QueryField\QueryGenerator($moduleName);
		if (!empty($cvId) && $cvId != 0) {
			$queryGenerator->initForCustomViewById($cvId);
		} else {
			$queryGenerator->initForDefaultCustomView();
		}
		$queryGenerator->setFields(['id']);

		$searchKey = $this->get('search_key');
		$searchValue = $this->get('search_value');
		$operator = $this->get('operator');
		if (!empty($searchValue)) {
			$queryGenerator->addBaseSearchConditions($searchKey, $searchValue, $operator);
		}
		$searchParams = $this->get('search_params');
		if (empty($searchParams)) {
			$searchParams = [];
		}
		$transformedSearchParams = $queryGenerator->parseBaseSearchParamsToCondition($searchParams);
		$queryGenerator->parseAdvFilter($transformedSearchParams);
		if ($module === 'RecycleBin') {
			$queryGenerator->deletedCondition = false;
			$queryGenerator->addNativeCondition(['vtiger_crmentity.deleted = 1']);
		}
		if (!empty($skipRecords) && count($skipRecords) > 0) {
			$queryGenerator->addNativeCondition(['not in', "$baseTableName.$baseTableId", $skipRecords]);
		}
		if ($lockRecords) {
			$lockFields = Vtiger_\App\Core\CRMEntity::getInstance($moduleName)->getLockFields();
			if (is_array($lockFields)) {
				foreach ($lockFields as $fieldName => $fieldValues) {
					$queryGenerator->addNativeCondition(['not in', "$baseTableName.$fieldName", $fieldValues]);
				}
			}
		}
		return $queryGenerator->createQuery()->column();
	}

	/**
	 * Function to save the custom view record
	 */
	public function save($request = null)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$currentUserModel = \App\User\CurrentUser::get();

		$cvIdOrg = $cvId = $this->getId();
		$setDefault = intval($this->get('setdefault'));
		$status = $this->get('status');
		$featured = $this->get('featured');

		if ($status == \App\View\CustomView::CV_STATUS_PENDING) {
			if ($currentUserModel->isAdminUser()) {
				$status = \App\View\CustomView::CV_STATUS_PUBLIC;
				$this->set('status', $status);
			}
		}
		$db->startTransaction();
		if (!$cvId) {
			$this->addCustomView();
			$cvId = $this->getId();
		} else {
			$this->updateCustomView();
		}

		$userId = 'Users:' . $currentUserModel->getId();
		if (!empty($featured) && empty($cvIdOrg)) {
			\App\Modules\Settings\CustomView\Models\Module::setFeaturedFilterView($cvId, $userId, 'add');
		} elseif (empty($featured) && !empty($cvIdOrg)) {
			\App\Modules\Settings\CustomView\Models\Module::setFeaturedFilterView($cvId, $userId, 'remove');
		} elseif (!empty($featured)) {
			$isExists = (new \App\Db\Query())->from('u_#__featured_filter')->where(['cvid' => $cvId, 'user' => $userId])->exists();
			if (!$isExists) {
				\App\Modules\Settings\CustomView\Models\Module::setFeaturedFilterView($cvId, $userId, 'add');
			}
		}
		if (empty($setDefault) && !empty($cvIdOrg)) {
			\App\Db\Db::getInstance()->createCommand()
				->delete('vtiger_user_module_preferences', ['userid' => $userId, 'tabid' => $this->getModule()->getId(), 'default_cvid' => $cvId])
				->execute();
		} elseif (!empty($setDefault)) {
			$this->setDefaultFilter();
		}
		$db->completeTransaction();
		\App\Cache\Cache::clear();
	}

	/**
	 * Function to delete the custom view record
	 */
	public function delete()
	{
		$db = \App\Db\Db::getInstance();
		$cvId = $this->getId();
		$db->createCommand()->delete('vtiger_customview', ['cvid' => $cvId])->execute();
		$db->createCommand()->delete('vtiger_cvcolumnlist', ['cvid' => $cvId])->execute();
		$db->createCommand()->delete('vtiger_cvstdfilter', ['cvid' => $cvId])->execute();
		$db->createCommand()->delete('vtiger_cvadvfilter', ['cvid' => $cvId])->execute();
		$db->createCommand()->delete('vtiger_cvadvfilter_grouping', ['cvid' => $cvId])->execute();
		$db->createCommand()->delete('vtiger_user_module_preferences', ['default_cvid' => $cvId])->execute();
		// To Delete the mini list widget associated with the filter 
		$db->createCommand()->delete('vtiger_module_dashboard', ['filterid' => $cvId])->execute();
		\App\Cache\Cache::clear();
	}

	/**
	 * Function to delete the custom view record
	 */
	public function setDefaultFilter()
	{
		$db = \App\Db\Db::getInstance();
		$currentUser = \App\User\CurrentUser::get();
		$userId = 'Users:' . $currentUser->getId();
		$tabId = $this->getModule()->getId();
		$db->createCommand()->delete('vtiger_user_module_preferences', ['userid' => $userId, 'tabid' => $tabId])->execute();
		$db->createCommand()->insert('vtiger_user_module_preferences', [
			'userid' => $userId,
			'tabid' => $tabId,
			'default_cvid' => $this->getId()
		])->execute();
	}

	public function setConditionsForFilter()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$db = \App\Db\Db::getInstance();
		$moduleModel = $this->getModule();
		$cvId = $this->getId();

		$stdFilterList = $this->get('stdfilterlist');
		if (!empty($stdFilterList) && !empty($stdFilterList['columnname'])) {
			$db->createCommand()
				->insert('vtiger_cvstdfilter', [
					'cvid' => $cvId,
					'columnname' => $stdFilterList['columnname'],
					'stdfilter' => $stdFilterList['stdfilter'],
					'startdate' => trim($stdFilterList['startdate'], "'"),
					'enddate' => trim($stdFilterList['enddate'], "'")
				])->execute();
		}

		$advFilterList = $this->get('advfilterlist');
		if (!empty($advFilterList)) {
			foreach ($advFilterList as $groupIndex => $groupInfo) {
				if (empty($groupInfo))
					continue;

				$groupColumns = $groupInfo['columns'];
				$groupCondition = isset($groupInfo['condition']) ? $groupInfo['condition'] : false;
				$groupConditionExpression = '';

				foreach ($groupColumns as $columnIndex => $columnCondition) {
					if (empty($columnCondition))
						continue;

					$advFilterColumn = $columnCondition['columnname'];
					$advFilterComparator = $columnCondition['comparator'];
					$advFitlerValue = (string) ($columnCondition['value'] ?? '');
					if (is_array($columnCondition['value'])) {
						$advFitlerValue = implode(',', $columnCondition['value']);
					}
					$advFilterColumnCondition = $columnCondition['column_condition'];

					$columnInfo = explode(":", $advFilterColumn);
					$fieldName = $columnInfo[2];
					$fieldModel = $moduleModel->getField($fieldName);
					//Required if Events module fields are selected for the condition
					if (!$fieldModel) {
						$modulename = $moduleModel->get('name');
						if ($modulename == 'Calendar') {
							$eventModuleModel = Vtiger_Module_model::getInstance('Events');
							$fieldModel = $eventModuleModel->getField($fieldName);
						}
					}
					$fieldType = $fieldModel->getFieldDataType();

					if ($fieldType == 'currency') {
						if ($fieldModel->get('uitype') == '72') {
							// Some of the currency fields like Unit Price, Totoal , Sub-total - doesn't need currency conversion during save
							$advFitlerValue = \App\Fields\CurrencyField::convertToDBFormat($advFitlerValue, null, true);
						} else {
							$advFitlerValue = \App\Fields\CurrencyField::convertToDBFormat($advFitlerValue);
						}
					}

					$temp_val = $advFitlerValue === '' ? [] : explode(",", $advFitlerValue);
					if (($fieldType == 'date' || ($fieldType == 'time' && $fieldName != 'time_start' && $fieldName != 'time_end') || ($fieldType == 'datetime')) && ($fieldType != '' && $advFitlerValue != '' )) {
						$val = Array();
						$countTempVal = count($temp_val);
						for ($x = 0; $x < $countTempVal; $x++) {
							//if date and time given then we have to convert the date and
							//leave the time as it is, if date only given then temp_time
							//value will be empty
							if (trim($temp_val[$x]) != '') {
								$date = new \App\Fields\DateTimeField(trim($temp_val[$x]));
								if ($fieldType == 'date') {
									$val[$x] = \App\Fields\DateTimeField::convertToDBFormat(
											trim($temp_val[$x]));
								} elseif ($fieldType == 'datetime') {
									$val[$x] = $date->getDBInsertDateTimeValue();
								} else {
									$val[$x] = $date->getDBInsertTimeValue();
								}
							}
						}
						$advFitlerValue = implode(",", $val);
					}
					if (in_array($advFilterComparator, ['om', 'wr', 'nwr', 'y', 'ny'])) {
						$advFitlerValue = '';
					}
					$db->createCommand()
						->insert('vtiger_cvadvfilter', [
							'cvid' => $cvId,
							'columnindex' => $columnIndex,
							'columnname' => $advFilterColumn,
							'comparator' => $advFilterComparator,
							'value' => $advFitlerValue,
							'groupid' => $groupIndex,
							'column_condition' => $advFilterColumnCondition
						])->execute();

					// Update the condition expression for the group to which the condition column belongs
					if (!empty($advFilterList[$groupIndex]["conditionexpression"])) {
						$groupConditionExpression = $advFilterList[$groupIndex]["conditionexpression"];
					}
					$groupConditionExpression = $groupConditionExpression . ' ' . $columnIndex . ' ' . $advFilterColumnCondition;
					$advFilterList[$groupIndex]["conditionexpression"] = $groupConditionExpression;
				}

				if (isset($advFilterList[$groupIndex]["conditionexpression"])) {
					$groupConditionExpression = $advFilterList[$groupIndex]["conditionexpression"];
					if (empty($groupConditionExpression)) {
						continue; // Case when the group doesn't have any column criteria
					}
				}
				$db->createCommand()
					->insert('vtiger_cvadvfilter_grouping', [
						'groupid' => $groupIndex,
						'cvid' => $cvId,
						'group_condition' => $groupCondition,
						'condition_expression' => $groupConditionExpression
					])->execute();
			}
		}
	}

	public function setColumnlist()
	{
		$db = \App\Db\Db::getInstance();
		$cvId = $this->getId();
		foreach ($this->get('columnslist') as $index => $columnName) {
			$db->createCommand()->insert('vtiger_cvcolumnlist', [
				'cvid' => $cvId,
				'columnindex' => $index,
				'columnname' => $columnName
			])->execute();
		}
	}

	/**
	 * Function to add the custom view record in db
	 */
	public function addCustomView()
	{
		$currentUser = \App\User\CurrentUser::get();
		$moduleName = $this->getModule()->get('name');
		$seq = $this->getNextSeq($moduleName);
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->insert('vtiger_customview', [
			'viewname' => $this->get('viewname'),
			'setmetrics' => $this->get('setmetrics'),
			'entitytype' => $moduleName,
			'status' => $this->get('status'),
			'userid' => $currentUser->getId(),
			'sequence' => $seq,
			'featured' => null,
			'color' => $this->get('color'),
			'description' => $this->get('description'),
			'sort' => (string) ($this->get('sort') ?? ''),
		])->execute();
		$this->set('cvid', $db->getLastInsertID('vtiger_customview_cvid_seq'));
		$this->setColumnlist();
		$this->setConditionsForFilter();
	}

	public function getNextSeq($moduleName)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT MAX(sequence) AS max  FROM vtiger_customview WHERE entitytype = ?;', [$moduleName]);
		$id = (int) $db->getSingleValue($result) + 1;
		return $id;
	}

	/**
	 * Function to update the custom view record in db
	 */
	public function updateCustomView()
	{
		$db = \App\Db\Db::getInstance();
		$cvId = $this->getId();
		$db->createCommand()->update('vtiger_customview', [
			'viewname' => $this->get('viewname'),
			'setmetrics' => $this->get('setmetrics'),
			'status' => $this->get('status'),
			'color' => $this->get('color'),
			'description' => $this->get('description'),
			'sort' => (string) ($this->get('sort') ?? ''),
			], ['cvid' => $cvId]
		)->execute();
		$db->createCommand()->delete('vtiger_cvcolumnlist', ['cvid' => $cvId])->execute();
		$db->createCommand()->delete('vtiger_cvstdfilter', ['cvid' => $cvId])->execute();
		$db->createCommand()->delete('vtiger_cvadvfilter', ['cvid' => $cvId])->execute();
		$db->createCommand()->delete('vtiger_cvadvfilter_grouping', ['cvid' => $cvId])->execute();
		$this->setColumnlist();
		$this->setConditionsForFilter();
	}

	/**
	 * Function to get the list of selected fields for the current custom view
	 * @return array List of Field Column Names
	 */
	public function getSelectedFields()
	{
		$cvId = $this->getId();
		if (!$cvId) {
			return [];
		}
		return (new \App\Db\Query())->select('vtiger_cvcolumnlist.columnindex, vtiger_cvcolumnlist.columnname')
				->from('vtiger_cvcolumnlist')
				->innerJoin('vtiger_customview', 'vtiger_cvcolumnlist.cvid = vtiger_customview.cvid')
				->where(['vtiger_customview.cvid' => $cvId])->orderBy('vtiger_cvcolumnlist.columnindex')
				->createCommand()->queryAllByGroup();
	}

	/**
	 * Function to get the Standard filter condition for the current custom view
	 * @return array Standard filter condition
	 */
	public function getStandardCriteria()
	{
		$db = \App\Database\PearDatabase::getInstance();

		$cvId = $this->getId();
		if (empty($cvId)) {
			return [];
		}

		$stdfilterlist = [];
		$query = 'SELECT vtiger_cvstdfilter.* FROM vtiger_cvstdfilter
					INNER JOIN vtiger_customview ON vtiger_customview.cvid = vtiger_cvstdfilter.cvid
				WHERE vtiger_cvstdfilter.cvid = ?';
		$params = array($this->getId());
		$result = $db->pquery($query, $params);
		$stdfilterrow = $db->fetch_array($result);
		if (!empty($stdfilterrow)) {
			$stdfilterlist["columnname"] = $stdfilterrow["columnname"];
			$stdfilterlist["stdfilter"] = $stdfilterrow["stdfilter"];

			if ($stdfilterrow["stdfilter"] == "custom" || $stdfilterrow["stdfilter"] == "") {
				if ($stdfilterrow["startdate"] != "0000-00-00" && $stdfilterrow["startdate"] != "") {
					$startDateTime = new \App\Fields\DateTimeField($stdfilterrow["startdate"] . ' ' . date('H:i:s'));
					$stdfilterlist["startdate"] = $startDateTime->getDisplayDate();
				}
				if ($stdfilterrow["enddate"] != "0000-00-00" && $stdfilterrow["enddate"] != "") {
					$endDateTime = new \App\Fields\DateTimeField($stdfilterrow["enddate"] . ' ' . date('H:i:s'));
					$stdfilterlist["enddate"] = $endDateTime->getDisplayDate();
				}
			} else { //if it is not custom get the date according to the selected duration
				$datefilter = DateTimeRange::getDateRangeByType($stdfilterrow['stdfilter']);
				$startDateTime = new \App\Fields\DateTimeField($datefilter[0] . ' ' . date('H:i:s'));
				$stdfilterlist["startdate"] = $startDateTime->getDisplayDate();
				$endDateTime = new \App\Fields\DateTimeField($datefilter[1] . ' ' . date('H:i:s'));
				$stdfilterlist["enddate"] = $endDateTime->getDisplayDate();
			}
		}
		return $stdfilterlist;
	}

	/**
	 * Function to get the list of advanced filter conditions for the current custom view
	 * @return array - All the advanced filter conditions for the custom view, grouped by the condition grouping
	 */
	public function getAdvancedCriteria()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$default_charset = \App\Core\AppConfig::main('default_charset');

		$cvId = $this->getId();
		$advft_criteria = [];
		if (empty($cvId)) {
			return $advft_criteria;
		}

		$sql = 'SELECT * FROM vtiger_cvadvfilter_grouping WHERE cvid = ? ORDER BY groupid';
		$groupsresult = $db->pquery($sql, array($this->getId()));

		$i = 1;
		$j = 0;
		while ($relcriteriagroup = $db->fetch_array($groupsresult)) {
			$groupId = $relcriteriagroup["groupid"];
			$groupCondition = $relcriteriagroup["group_condition"];

			$ssql = 'select vtiger_cvadvfilter.* from vtiger_customview
						inner join vtiger_cvadvfilter on vtiger_cvadvfilter.cvid = vtiger_customview.cvid
						left join vtiger_cvadvfilter_grouping on vtiger_cvadvfilter.cvid = vtiger_cvadvfilter_grouping.cvid
								and vtiger_cvadvfilter.groupid = vtiger_cvadvfilter_grouping.groupid';
			$ssql .= " where vtiger_customview.cvid = ? AND vtiger_cvadvfilter.groupid = ? order by vtiger_cvadvfilter.columnindex";

			$result = $db->pquery($ssql, array($this->getId(), $groupId));
			$noOfColumns = $db->num_rows($result);
			if ($noOfColumns <= 0)
				continue;

			while ($relcriteriarow = $db->fetch_array($result)) {
				$criteria = [];
				$criteria['columnname'] = html_entity_decode($relcriteriarow["columnname"], ENT_QUOTES, $default_charset);
				$criteria['comparator'] = $relcriteriarow["comparator"];
				$rowValue = (string) ($relcriteriarow["value"] ?? '');
				$advfilterval = html_entity_decode($rowValue, ENT_QUOTES, $default_charset);
				$col = explode(":", $relcriteriarow["columnname"]);
				$temp_val = $rowValue === '' ? [] : explode(",", $rowValue);
				if ($col[4] == 'D' || ($col[4] == 'T' && $col[1] != 'time_start' && $col[1] != 'time_end') || ($col[4] == 'DT')) {
					$val = Array();
					$countTempVal = count($temp_val);
					for ($x = 0; $x < $countTempVal; $x++) {
						if ($col[4] == 'D') {
							/** while inserting in db for due_date it was taking date and time values also as it is 
							 * date time field. We only need to take date from that value
							 */
							if ($col[0] == 'vtiger_activity' && $col[1] == 'due_date') {
								$originalValue = $temp_val[$x];
								$dateTime = explode(' ', $originalValue);
								$temp_val[$x] = $dateTime[0];
							}
							$date = new \App\Fields\DateTimeField(trim($temp_val[$x]));
							$val[$x] = $date->getDisplayDate();
						} elseif ($col[4] == 'DT') {
							$comparator = array('e', 'n', 'b', 'a');
							if (in_array($criteria['comparator'], $comparator)) {
								$originalValue = $temp_val[$x];
								$dateTime = explode(' ', $originalValue);
								$temp_val[$x] = $dateTime[0];
							}
							$date = new \App\Fields\DateTimeField(trim($temp_val[$x]));
							$val[$x] = $date->getDisplayDateTimeValue();
						} else {
							$date = new \App\Fields\DateTimeField(trim($temp_val[$x]));
							$val[$x] = $date->getDisplayTime();
						}
					}
					$advfilterval = implode(",", $val);
				}
				$criteria['value'] = \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\ListViewUtils::decodeHtml($advfilterval));
				$criteria['column_condition'] = $relcriteriarow["column_condition"];

				$groupId = $relcriteriarow['groupid'];
				$advft_criteria[$groupId]['columns'][$j] = $criteria;
				$advft_criteria[$groupId]['condition'] = $groupCondition;
				$j++;
			}
			if (!empty($advft_criteria[$groupId]['columns'][$j - 1]['column_condition'])) {
				$advft_criteria[$groupId]['columns'][$j - 1]['column_condition'] = '';
			}
			$i++;
		}
		// Clear the condition (and/or) for last group, if any.
		if (!empty($advft_criteria[$i - 1]['condition']))
			$advft_criteria[$i - 1]['condition'] = '';
		return $advft_criteria;
	}

	/**
	 * Function returns standard filter sql
	 * @return string
	 */
	public function getCVStdFilterSQL()
	{
		$customView = new \App\View\CustomView();
		return $customView->getCVStdFilterSQL($this->getId());
	}

	/**
	 * Function returns Advanced filter sql
	 * @return string
	 */
	public function getCVAdvFilterSQL()
	{
		$customView = new \App\View\CustomView();
		return $customView->getCVAdvFilterSQL($this->getId());
	}

	/**
	 * Function returns approve url
	 * @return String - approve url
	 */
	public function getCreateUrl()
	{
		return 'index.php?module=CustomView&view=EditAjax&source_module=' . $this->getModule()->get('name');
	}

	/**
	 * Function returns approve url
	 * @return String - approve url
	 */
	public function getEditUrl()
	{
		return 'module=CustomView&view=EditAjax&source_module=' . $this->getModule()->get('name') . '&record=' . $this->getId();
	}

	/**
	 * Function returns approve url
	 * @return String - approve url
	 */
	public function getApproveUrl()
	{
		return 'index.php?module=CustomView&action=Approve&sourceModule=' . $this->getModule()->get('name') . '&record=' . $this->getId();
	}

	/**
	 * Function returns deny url
	 * @return String - deny url
	 */
	public function getDenyUrl()
	{
		return 'index.php?module=CustomView&action=Deny&sourceModule=' . $this->getModule()->get('name') . '&record=' . $this->getId();
	}

	/**
	 * Function returns duplicate url
	 * @return String - duplicate url
	 */
	public function getDuplicateUrl()
	{
		return 'module=CustomView&view=EditAjax&source_module=' . $this->getModule()->get('name') . '&record=' . $this->getId() . '&duplicate=1';
	}

	/**
	 * Functions returns delete url
	 * @return String - delete url
	 */
	public function getDeleteUrl()
	{
		return 'index.php?module=CustomView&action=Delete&sourceModule=' . $this->getModule()->get('name') . '&record=' . $this->getId();
	}

	public function approve()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$db->pquery('UPDATE vtiger_customview SET status = ? WHERE cvid = ?', array(\App\View\CustomView::CV_STATUS_PUBLIC, $this->getId()));
	}

	public function deny()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$db->pquery('UPDATE vtiger_customview SET status = ? WHERE cvid = ?', array(\App\View\CustomView::CV_STATUS_PRIVATE, $this->getId()));
	}

	/**
	 * Function to get the advanced filter option names by Field type
	 * @return array
	 */
	public static function getAdvancedFilterOpsByFieldType()
	{
		return array(
			'V' => array('e', 'n', 's', 'ew', 'c', 'k'),
			'N' => array('e', 'n', 'l', 'g', 'm', 'h'),
			'T' => array('e', 'n', 'l', 'g', 'm', 'h', 'bw', 'b', 'a'),
			'I' => array('e', 'n', 'l', 'g', 'm', 'h'),
			'C' => array('e', 'n'),
			'D' => array('e', 'n', 'bw', 'b', 'a'),
			'DT' => array('e', 'n', 'bw', 'b', 'a'),
			'NN' => array('e', 'n', 'l', 'g', 'm', 'h'),
			'E' => array('e', 'n', 's', 'ew', 'c', 'k')
		);
	}

	/**
	 * Function to get all the accessible Custom Views, for a given module if specified
	 * @param string $moduleName
	 * @return \App\Modules\CustomView\Models\Record[]
	 */
	public static function getAll($moduleName = '')
	{

		\App\Log\Log::trace('Entering ' . __METHOD__ . " ($moduleName) method ...");
		$currentUser = \App\User\CurrentUser::get();
		$cacheName = $moduleName . $currentUser->getId();
		if (\App\Cache\Cache::has('getAllFilters', $cacheName)) {
			return \App\Cache\Cache::get('getAllFilters', $cacheName);
		}
		$db = \App\Database\PearDatabase::getInstance();
		$sql = 'SELECT * FROM vtiger_customview';
		$params = [];
		if (!empty($moduleName)) {
			$sql .= ' WHERE entitytype=?';
			$params[] = $moduleName;
		}
		if (!$currentUser->isAdminUser()) {
			$userParentRoleSeq = $currentUser->getParentRoleSequence();
			$sql .= " AND ( vtiger_customview.userid = ? OR vtiger_customview.status = 0 OR vtiger_customview.status = 3
							OR vtiger_customview.userid IN (
								SELECT vtiger_user2role.userid FROM vtiger_user2role
									INNER JOIN vtiger_users ON vtiger_users.id = vtiger_user2role.userid
									INNER JOIN vtiger_role ON vtiger_role.roleid = vtiger_user2role.roleid
								WHERE vtiger_role.parentrole LIKE '" . $userParentRoleSeq . "::%')
						)";
			$params[] = $currentUser->getId();
		}
		$sql .= ' ORDER BY sequence ASC';
		$result = $db->pquery($sql, $params);
		$customViews = [];
		while ($row = $db->fetch_array($result)) {
			$customView = new self();
			if (strlen(\App\Utils\ListViewUtils::decodeHtml($row['viewname'])) > 40) {
				$row['viewname'] = substr(\App\Utils\ListViewUtils::decodeHtml($row['viewname']), 0, 36) . '...';
			}
			$customViews[$row['cvid']] = $customView->setData($row)->setModule($row['entitytype']);
		}

		$filterDir = 'modules' . DIRECTORY_SEPARATOR . $moduleName . DIRECTORY_SEPARATOR . 'filters';
		if ($moduleName && file_exists($filterDir)) {
			$view = ['setdefault' => 0, 'setmetrics' => 0, 'status' => 0, 'privileges' => 0];
			$filters = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($filterDir, FilesystemIterator::SKIP_DOTS));
			foreach ($filters as $filter) {
				$name = str_replace('.php', '', $filter->getFilename());
				$handlerClass = \App\Core\Loader::getComponentClassName('Filter', $name, $moduleName);
				if (class_exists($handlerClass)) {
					$handler = new $handlerClass();
					$view['viewname'] = $handler->getViewName();
					$view['cvid'] = $name;
					$view['status'] = \App\View\CustomView::CV_STATUS_SYSTEM;
					$customView = new self();
					$customViews[$name] = $customView->setData($view)->setModule($moduleName);
				}
			}
		}
		\App\Cache\Cache::save('getAllFilters', $cacheName, $customViews, \App\Cache\Cache::LONG);
		\App\Log\Log::trace('Exiting ' . __METHOD__ . ' method ...');
		return $customViews;
	}

	/**
	 * Function to get the instance of Custom View module, given custom view id
	 * @param int $cvId
	 * @param string $module (unused, for parent compatibility)
	 * @return \App\Modules\CustomView\Models\Record instance, if exists. Null otherwise
	 */
	public static function getInstanceById($cvId, $module = null)
	{
		if (\App\Cache\Cache::has('\App\Modules\CustomView\Models\RecordgetInstanceById', $cvId)) {
			$row = \App\Cache\Cache::get('\App\Modules\CustomView\Models\RecordgetInstanceById', $cvId);
		} else {
			$row = (new \App\Db\Query())->from('vtiger_customview')->where(['cvid' => $cvId])->one();
			\App\Cache\Cache::save('\App\Modules\CustomView\Models\RecordgetInstanceById', $cvId, $row, \App\Cache\Cache::LONG);
		}
		if ($row) {
			$customView = new self();
			return $customView->setData($row)->setModule($row['entitytype']);
		}
		return null;
	}

	/**
	 * Function to get all the custom views, of a given module if specified, grouped by their status
	 * @param string $moduleName
	 * @return \App\Modules\CustomView\Models\Record[] grouped by status
	 */
	public static function getAllByGroup($moduleName = '', $menuId = false)
	{
		$customViews = self::getAll($moduleName);
		$filters = [];
		$menuFilter = false;
		if ($menuId) {
			$userPrivModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
			$menuPrivileges = \App\Modules\Base\Models\Menu::loadPrivilegeFile($userPrivModel->get('roleid'));
			$filterList = $menuPrivileges['filterList'];
			if (array_key_exists($menuId, $filterList)) {
				$filters = explode(',', $filterList[$menuId]['filters']);
				$menuFilter = true;
			}
		}
		$currentUserId = \App\User\CurrentUser::get()->getId();
		$standard = $mine = $byOwner = [];
		foreach ($customViews as $customView) {
			if ($menuFilter && !in_array($customView->getId(), $filters)) {
				continue;
			}
			$status = (int) $customView->get('status');
			if ($customView->isSystem() || ($customView->get('viewname') === 'All' && $status === \App\View\CustomView::CV_STATUS_DEFAULT)) {
				$standard[] = $customView;
			} elseif ((int) $customView->get('userid') === $currentUserId) {
				$mine[] = $customView;
			} else {
				$byOwner[$customView->getOwnerName()][] = $customView;
			}
		}
		ksort($byOwner, SORT_NATURAL | SORT_FLAG_CASE);

		$groupedCustomViews = [];
		if ($standard) {
			$groupedCustomViews[\App\Runtime\Vtiger_Language_Handler::translate('LBL_CV_GROUP_SYSTEM')] = $standard;
		}
		if ($mine) {
			$groupedCustomViews[\App\Runtime\Vtiger_Language_Handler::translate('LBL_CV_GROUP_MINE')] = $mine;
		}
		foreach ($byOwner as $ownerName => $ownerViews) {
			$groupedCustomViews[$ownerName] = $ownerViews;
		}
		return $groupedCustomViews;
	}

	/**
	 * Function to get Clean instance of this record
	 * @param string $moduleName
	 * @return self
	 */
	public static function getCleanInstance($moduleName = null)
	{
		return new self();
	}

	/**
	 * Function used to transform the older filter condition to suit newer filters.
	 * The newer filters have only two groups one with ALL(AND) condition between each
	 * filter and other with ANY(OR) condition, this functions tranforms the older
	 * filter with 'AND' condition between filters of a group and will be placed under
	 * match ALL conditions group and the rest of it will be placed under match Any group.
	 * @return array
	 */
	public function transformToNewAdvancedFilter()
	{
		$standardFilter = $this->transformStandardFilter();
		$advancedFilter = $this->getAdvancedCriteria();
		$allGroupColumns = $anyGroupColumns = [];
		foreach ($advancedFilter as $index => $group) {
			$columns = $group['columns'];
			$and = $or = 0;
			$block = $group['condition'];
			if (count($columns) != 1) {
				foreach ($columns as $column) {
					if ($column['column_condition'] == 'and') {
						++$and;
					} else {
						++$or;
					}
				}
				if ($and == count($columns) - 1 && count($columns) != 1) {
					$allGroupColumns = array_merge($allGroupColumns, $group['columns']);
				} else {
					$anyGroupColumns = array_merge($anyGroupColumns, $group['columns']);
				}
			} else if ($block == 'and' || $index == 1) {
				$allGroupColumns = array_merge($allGroupColumns, $group['columns']);
			} else {
				$anyGroupColumns = array_merge($anyGroupColumns, $group['columns']);
			}
		}
		if ($standardFilter) {
			$allGroupColumns = array_merge($allGroupColumns, $standardFilter);
		}
		$transformedAdvancedCondition = [];
		$transformedAdvancedCondition[1] = array('columns' => $allGroupColumns, 'condition' => 'and');
		$transformedAdvancedCondition[2] = array('columns' => $anyGroupColumns, 'condition' => '');

		return $transformedAdvancedCondition;
	}
	/*
	 * Function used to tranform the standard filter as like as advanced filter format
	 * @returns array of tranformed standard filter
	 */

	public function transformStandardFilter()
	{
		$standardFilter = $this->getStandardCriteria();
		if (!empty($standardFilter)) {
			$tranformedStandardFilter = [];
			$tranformedStandardFilter['comparator'] = 'bw';

			$fields = explode(':', $standardFilter['columnname']);

			if ($fields[1] == 'createdtime' || $fields[1] == 'modifiedtime' || ($fields[0] == 'vtiger_activity' && $fields[1] == 'date_start')) {
				$tranformedStandardFilter['columnname'] = $standardFilter['columnname'] . ':DT';
				$date[] = $standardFilter['startdate'] . ' 00:00:00';
				$date[] = $standardFilter['enddate'] . ' 00:00:00';
				$tranformedStandardFilter['value'] = implode(',', $date);
			} else {
				$tranformedStandardFilter['columnname'] = $standardFilter['columnname'] . ':D';
				$tranformedStandardFilter['value'] = $standardFilter['startdate'] . ',' . $standardFilter['enddate'];
			}
			return array($tranformedStandardFilter);
		} else {
			return false;
		}
	}

	/**
	 * Function gives default custom view for a module
	 * @param string $module
	 * @return \App\Modules\CustomView\Models\Record
	 */
	public static function getAllFilterByModule($module)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$query = "SELECT cvid FROM vtiger_customview WHERE viewname='All' AND entitytype = ?";
		$result = $db->pquery($query, array($module));
		$viewId = $db->query_result($result, 0, 'cvid');
		if (!$viewId) {
			$viewId = \App\View\CustomView::getInstance($module)->getViewId();
		}
		return self::getInstanceById($viewId);
	}

	/**
	 * Parse vtiger_customview.sort — canonical format: {column},{ASC|DESC}
	 *
	 * @return array{orderBy: string, sortOrder: string}
	 */
	public static function parseSortValue(?string $sort): array
	{
		if ($sort === null || $sort === '') {
			return ['orderBy' => '', 'sortOrder' => ''];
		}
		$parts = explode(',', $sort, 2);
		$orderBy = trim($parts[0]);
		$sortOrder = strtoupper(trim($parts[1] ?? 'ASC'));
		if ($sortOrder !== 'DESC') {
			$sortOrder = 'ASC';
		}
		return ['orderBy' => $orderBy, 'sortOrder' => $sortOrder];
	}

	/**
	 * Build vtiger_customview.sort from field column + direction.
	 */
	public static function formatSortValue(?string $orderBy, ?string $sortOrder = null): string
	{
		$orderBy = trim((string) $orderBy);
		if ($orderBy === '') {
			return '';
		}
		$parsed = self::parseSortValue($orderBy . ',' . ($sortOrder ?? 'ASC'));
		return $parsed['orderBy'] !== '' ? $parsed['orderBy'] . ',' . $parsed['sortOrder'] : '';
	}

	public function getSortOrderBy($name = '')
	{
		if ($this->sortOrderBy === false) {
			$parsed = self::parseSortValue($this->get('sort'));
			$this->sortOrderBy = [$parsed['orderBy'], $parsed['sortOrder']];
		}
		$return = $this->sortOrderBy;
		switch ($name) {
			case 'orderBy':
				$return = isset($return[0]) ? $return[0] : '';
				break;
			case 'sortOrder':
				$return = isset($return[1]) ? $return[1] : '';
				break;

			default:
				break;
		}
		return $return;
	}
}
