<?php

namespace App\Modules\Calendar\Models;

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
 * Vtiger ListView Model Class
 */
class ListView extends \App\Modules\Base\Models\ListView
{

	public function getBasicLinks()
	{
		$basicLinks = [];
		$moduleModel = $this->getModule();
		$createPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'CreateView');
		if ($createPermission) {
			$basicLinks[] = [
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_ADD_EVENT',
				'linkurl' => $this->getModule()->getCreateEventRecordUrl(),
				'linkclass' => 'moduleColor_' . $moduleModel->getName(),
				'linkicon' => '',
				'showLabel' => 1,
			];
			$basicLinks[] = [
				'linktype' => 'LISTVIEWBASIC',
				'linklabel' => 'LBL_ADD_TASK',
				'linkurl' => $this->getModule()->getCreateTaskRecordUrl(),
				'linkclass' => 'moduleColor_' . $moduleModel->getName(),
				'linkicon' => '',
				'showLabel' => 1,
			];
		}
		return $basicLinks;
	}
	/*
	 * Function to give advance links of a module
	 * 	@RETURN array of advanced links
	 */

	public function getAdvancedLinks()
	{
		$moduleModel = $this->getModule();
		$createPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'CreateView') && \App\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'EditView');
		$advancedLinks = [];
		$importPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'Import');
		if ($importPermission && $createPermission) {
			$advancedLinks[] = array(
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_IMPORT',
				'linkurl' => 'javascript:Calendar_List_Js.triggerImportAction("' . $moduleModel->getImportUrl() . '")',
				'linkicon' => ''
			);
		}

		$exportPermission = \App\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'Export');
		if ($exportPermission) {
			$advancedLinks[] = array(
				'linktype' => 'LISTVIEW',
				'linklabel' => 'LBL_EXPORT',
				'linkurl' => 'javascript:Calendar_List_Js.triggerExportAction("' . $this->getModule()->getExportUrl() . '")',
				'linkicon' => ''
			);
		}
		return $advancedLinks;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param array $linkParams
	 * @return array - Associative array of Link type to List of  \App\Modules\Base\Models\Link instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams)
	{
		$moduleModel = $this->getModule();
		$links = \App\Modules\Base\Models\Link::getAllByType($moduleModel->getId(), ['LISTVIEWMASSACTION'], $linkParams);

		$massActionLinks = [];
		if ($moduleModel->isPermitted('MassTransferOwnership')) {
			$massActionLinks[] = [
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_TRANSFER_OWNERSHIP',
				'linkurl' => 'javascript:Vtiger_ListView_Js.triggerTransferOwnership("index.php?module=' . $moduleModel->getName() . '&view=MassActionAjax&mode=transferOwnership")',
				'linkicon' => ''
			];
		}
		if ($moduleModel->isPermitted('MassDelete')) {
			$massActionLinks[] = [
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_DELETE',
				'linkurl' => 'javascript:Vtiger_ListView_Js.massDeleteRecords("index.php?module=' . $moduleModel->get('name') . '&action=MassDelete");',
				'linkicon' => ''
			];
		}

		foreach ($massActionLinks as $massActionLink) {
			$links['LISTVIEWMASSACTION'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($massActionLink);
		}

		return $links;
	}

	/**
	 * Function to get the list view entries
	 * @param \App\Modules\Base\Models\Paging $pagingModel
	 * @return array - Associative array of record id mapped to \App\Modules\Base\Models\Record instance.
	 */
	public function getListViewEntries(\App\Modules\Base\Models\Paging $pagingModel)
	{
		$queryGenerator = $this->get('query_generator');
		$queryGenerator->setField(['visibility', 'assigned_user_id', 'activitystatus']);
		$queryGenerator->setConcatColumn('date_start', "CONCAT(vtiger_activity.date_start, ' ', vtiger_activity.time_start)");
		$queryGenerator->setConcatColumn('due_date', "CONCAT(vtiger_activity.due_date, ' ', vtiger_activity.time_end)");
		return parent::getListViewEntries($pagingModel);
	}
}
