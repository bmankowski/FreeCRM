<?php

namespace App\Modules\HelpDesk\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Record extends \App\Modules\Base\Models\Record
{

	/**
	 * Function to get URL for Convert FAQ
	 * @return string
	 */
	public function getConvertFAQUrl()
	{
		return "index.php?module=" . $this->getModuleName() . "&action=ConvertFAQ&record=" . $this->getId();
	}

	/**
	 * Function to get Comments List of this Record
	 * @return string
	 */
	public function getCommentsList()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$commentsList = array();

		$result = $db->pquery("SELECT commentcontent AS comments FROM vtiger_modcomments WHERE related_to = ?", array($this->getId()));
		$numOfRows = $db->num_rows($result);

		for ($i = 0; $i < $numOfRows; $i++) {
			array_push($commentsList, $db->query_result($result, $i, 'comments'));
		}

		return $commentsList;
	}

	/**
	 * @return array<int, array{id: int|string, orgname: string, path: string, name: string, url: string}>
	 */
	public function getImageDetails(): array
	{
		return \App\Models\RecordFile::getImageDetailsForRecord((int) $this->getId(), 'HelpDesk');
	}

	public function attachReportIssueScreenshot(array $fileDetails): string
	{
		$fileInstance = \App\Fields\File::loadFromRequest($fileDetails);
		if (!$fileInstance->validate('image')) {
			return '';
		}

		if (!$this->uploadAndSaveFile($fileDetails, 'Attachment', 'HelpDesk')) {
			return '';
		}

		$id = $this->getId();
		$row = \App\Models\RecordFile::getByRecord((int) $id, \App\Models\RecordFile::ROLE_IMAGE);
		$imageName = $row ? \App\Utils\ListViewUtils::decodeHtml((string) ($row['original_name'] ?? '')) : '';

		\App\Db\Db::getInstance()->createCommand()->update(
			'vtiger_troubletickets',
			['report_issue_screenshot' => $imageName],
			['ticketid' => $id]
		)->execute();
		$this->set('report_issue_screenshot', $imageName);

		return (string) ($fileDetails['name'] ?? $imageName);
	}

	public static function updateTicketRangeTimeField($recordModel, $updateFieldImmediately = false)
	{
		if (!$recordModel->isNew() && ($recordModel->getPreviousValue('ticketstatus') || $updateFieldImmediately)) {
			$currentDate = date('Y-m-d H:i:s');
			if (in_array($recordModel->get('ticketstatus'), ['Closed', 'Rejected'])) {
				$currentDate = null;
			}
			\App\Db\Db::getInstance()->createCommand()
				->update('vtiger_troubletickets', [
					'response_time' => $currentDate,
					], ['ticketid' => $recordModel->getId()])
				->execute();
		}
		$closedTime = $recordModel->get('closedtime');
		if (!empty($closedTime) && $recordModel->has('report_time')) {
			$timeMinutesRange = round(\vtlib\Functions:: getDateTimeMinutesDiff($recordModel->get('createdtime'), $closedTime));
			if (!empty($timeMinutesRange)) {
				\App\Db\Db::getInstance()->createCommand()
					->update('vtiger_troubletickets', ['report_time' => $timeMinutesRange], ['ticketid' => $recordModel->getId()])
					->execute();
			}
		}
	}

	public function getActiveServiceContracts()
	{
		$query = (new \App\Db\Query())->from('vtiger_servicecontracts')
			->innerJoin('vtiger_crmentity', 'vtiger_servicecontracts.servicecontractsid = vtiger_crmentity.crmid')
			->where(['deleted' => 0, 'contract_status' => 'In Progress', 'sc_related_to' => $this->get('parent_id')]);
		\App\Security\PrivilegeQuery::getConditions($query, 'ServiceContracts');
		return $query->all();
	}

	/**
	 * Function to save record
	 * @param array $relationParams Optional relation parameters
	 */
	public function saveToDb($relationParams = null)
	{
		parent::saveToDb($relationParams);
		
		if ($relationParams && !empty($relationParams['return_action'])) {
			$forModule = $relationParams['return_module'] ?? null;
			$forCrmid = $relationParams['return_id'] ?? null;
			$currentModule = $relationParams['current_module'] ?? null;
			
			if ($forModule && $forCrmid && $forModule === 'ServiceContracts') {
				\App\Core\CRMEntity::getInstance($forModule)->save_related_module(
					$forModule, 
					$forCrmid, 
					$currentModule, 
					$this->getId()
				);
			}
		}
	}
}
