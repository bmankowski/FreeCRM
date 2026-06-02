<?php

namespace App\Modules\Reports\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Module extends \App\Modules\Base\Models\Module
{

	/**
	 * Function deletes report
	 * @param \App\Modules\Reports\Models\Record $reportModel
	 */
	public function deleteRecord($reportModel)
	{
		$currentUser = \App\User\CurrentUser::get();
		$subOrdinateUsers = $currentUser->getSubordinateUsers();

		$subOrdinates = array();
		foreach ($subOrdinateUsers as $id => $name) {
			$subOrdinates[] = $id;
		}

		$owner = $reportModel->get('owner');

		if ($currentUser->isAdminUser() || in_array($owner, $subOrdinates) || $owner == $currentUser->getId()) {
			$reportId = $reportModel->getId();
			$db = \App\Database\PearDatabase::getInstance();

			$db->pquery('DELETE FROM vtiger_selectquery WHERE queryid = ?', array($reportId));

			$db->pquery('DELETE FROM vtiger_report WHERE reportid = ?', array($reportId));

			$db->pquery('DELETE FROM vtiger_schedulereports WHERE reportid = ?', array($reportId));

			$db->pquery('DELETE FROM vtiger_reporttype WHERE reportid = ?', array($reportId));

			$result = $db->pquery('SELECT * FROM vtiger_homereportchart WHERE reportid = ?', array($reportId));
			$numOfRows = $db->num_rows($result);
			for ($i = 0; $i < $numOfRows; $i++) {
				$homePageChartIdsList[] = $adb->query_result($result, $i, 'stuffid');
			}
			if ($homePageChartIdsList) {
				$where = sprintf('stuffid IN (%s)', implode(",", $homePageChartIdsList));
				$db->delete('vtiger_homestuff', $where);
			}
			return true;
		}
		return false;
	}

	/**
	 * Function returns quick links for the module
	 * @return array
	 */
	public function getSideBarLinks($linkParams = '', ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		$quickLinks = array(
			array(
				'linktype' => 'SIDEBARLINK',
				'linklabel' => 'LBL_REPORTS',
				'linkurl' => $this->getListViewUrl(),
				'linkicon' => '',
			),
		);
		foreach ($quickLinks as $quickLink) {
			$links['SIDEBARLINK'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($quickLink);
		}

		$quickWidgets = array(
			array(
				'linktype' => 'SIDEBARWIDGET',
				'linklabel' => 'LBL_RECENTLY_MODIFIED',
				'linkurl' => 'module=' . $this->get('name') . '&view=IndexAjax&mode=showActiveRecords',
				'linkicon' => ''
			),
		);
		foreach ($quickWidgets as $quickWidget) {
			$links['SIDEBARWIDGET'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($quickWidget);
		}

		return $links;
	}

	/**
	 * Function returns the recent created reports
	 * @param mixed $limit
	 * @return array
	 */
	public function getRecentRecords(int $userId, int $limit = 10)
	{
		$db = \App\Database\PearDatabase::getInstance();

		$result = $db->pquery('SELECT * FROM vtiger_report ORDER BY reportid DESC LIMIT ?', array($limit));
		$rows = $db->num_rows($result);

		$recentRecords = array();
		for ($i = 0; $i < $rows; ++$i) {
			$row = $db->query_result_rowdata($result, $i);
			$recentRecords[$row['reportid']] = $this->getRecordFromArray($row);
		}
		return $recentRecords;
	}

	/**
	 * Function returns the report folders
	 * @return array
	 */
	public function getFolders()
	{
		return \App\Modules\Reports\Models\Folder::getAll();
	}

	/**
	 * Function to get the url for add folder from list view of the module
	 * @return string - url
	 */
	public function getAddFolderUrl()
	{
		return 'index.php?module=' . $this->get('name') . '&view=EditFolder';
	}

	/**
	 * Function to check if the extension module is permitted for utility action
	 * @return bool true
	 */
	public function isUtilityActionEnabled()
	{
		return true;
	}
}
