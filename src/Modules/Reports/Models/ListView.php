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

/**
 * Reports ListView Model Class
 */
class ListView extends \App\Modules\Base\Models\ListView
{

	/**
	 * Function to get the list of listview links for the module
	 * @return array - Associate array of Link Type to List of \App\Modules\Base\Models\Link instances
	 */
	public function getListViewLinks($linkParams, ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		$currentUserModel = \App\User\CurrentUser::get();
		$privileges = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$basicLinks = array();
		if ($currentUserModel->isAdminUser() || $privileges->hasModulePermission($this->getModule()->getId())) {
			$basicLinks = array(
				array(
					'linktype' => 'LISTVIEWBASIC',
					'linklabel' => 'LBL_ADD_RECORD',
					'linkurl' => $this->getCreateRecordUrl(),
					'linkicon' => '',
					'childlinks' => array(
						array(
							'linktype' => 'LISTVIEWBASIC',
							'linklabel' => 'LBL_DETAIL_REPORT',
							'linkurl' => $this->getCreateRecordUrl(),
							'linkicon' => '',
						),
						array(
							'linktype' => 'LISTVIEWBASIC',
							'linklabel' => 'LBL_CHARTS',
							'linkurl' => 'javascript:Reports_ListView_Js.addReport("index.php?module=' . $this->getModule()->get('name') . '&view=ChartEdit")',
							'linkicon' => '',
						)
					)
				),
				array(
					'linktype' => 'LISTVIEWBASIC',
					'linklabel' => 'LBL_ADD_FOLDER',
					'linkurl' => 'javascript:Reports_ListView_Js.triggerAddFolder("' . $this->getModule()->getAddFolderUrl() . '")',
					'linkicon' => ''
				)
			);
		}

		foreach ($basicLinks as $basicLink) {
			$headerLinkInstance = \App\Modules\Base\Models\Link::getInstanceFromValues($basicLink);
			$headerLinkInstance->setChildLink([]);
			if (!empty($basicLink['childlinks'])) {
				foreach ($basicLink['childlinks'] as &$childLink) {
					$headerLinkInstance->addChildLink(\App\Modules\Base\Models\Link::getInstanceFromValues($childLink));
				}
			}
			$links['LISTVIEWBASIC'][] = $headerLinkInstance;
		}

		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param array $linkParams
	 * @return array - Associative array of Link type to List of  \App\Modules\Base\Models\Link instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams, ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		$currentUserModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();

		$massActionLinks = array();
		if ($currentUserModel->hasModulePermission($this->getModule()->getId())) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_DELETE',
				'linkurl' => 'javascript:Reports_ListView_Js.massDelete("index.php?module=' . $this->getModule()->get('name') . '&action=MassDelete");',
				'linkicon' => ''
			);

			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_MOVE_REPORT',
				'linkurl' => 'javascript:Reports_ListView_Js.massMove("index.php?module=' . $this->getModule()->get('name') . '&view=MoveReports");',
				'linkicon' => ''
			);
		}

		foreach ($massActionLinks as $massActionLink) {
			$links[] = \App\Modules\Base\Models\Link::getInstanceFromValues($massActionLink);
		}

		return $links;
	}

	/**
	 * Function to get the list view header
	 * @return array - List of \App\Modules\Base\Models\Field instances
	 */
	public function getListViewHeaders()
	{
		return array(
			'reportname' => 'Report Name',
			'description' => 'Description'
		);
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Base\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Base\Models\Record instance.
	 */
	public function getListViewEntries(\App\Modules\Base\Models\Paging $pagingModel, $searchResult = false)
	{
		$reportFolderModel = \App\Modules\Reports\Models\Folder::getInstance();
		$reportFolderModel->set('folderid', $this->get('folderid'));

		$orderBy = $this->get('orderby');
		if (!empty($orderBy) && $orderBy === 'smownerid') {
			$fieldModel = \App\Modules\Base\Models\Field::getInstance('assigned_user_id', $moduleModel);
			if ($fieldModel->getFieldDataType() == 'owner') {
				$orderBy = 'COALESCE(' . \vtlib\Deprecated::getSqlForNameInDisplayFormat(['first_name' => 'vtiger_users.first_name', 'last_name' => 'vtiger_users.last_name'], 'Users') . ',vtiger_groups.groupname)';
			}
		}
		if (!empty($orderBy)) {
			$reportFolderModel->set('orderby', $orderBy);
			$reportFolderModel->set('sortby', $this->get('sortorder'));
		}

		$reportRecordModels = $reportFolderModel->getReports($pagingModel);
		$pagingModel->calculatePageRange(count($reportRecordModels));
		return $reportRecordModels;
	}

	/**
	 * Function to get the list view entries count
	 * @return int
	 */
	public function getListViewCount(): int
	{
		$reportFolderModel = \App\Modules\Reports\Models\Folder::getInstance();
		$reportFolderModel->set('folderid', $this->get('folderid'));
		return (int) $reportFolderModel->getReportsCount();
	}

	public function getCreateRecordUrl()
	{
		return 'javascript:Reports_ListView_Js.addReport("' . $this->getModule()->getCreateRecordUrl() . '")';
	}
}
