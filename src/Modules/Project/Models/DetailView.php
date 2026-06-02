<?php

namespace App\Modules\Project\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class DetailView extends \App\Modules\Base\Models\DetailView
{

	public function getDetailViewLinks($linkParams)
	{
		$currentUserModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$recordModel = $this->getRecord();
		$linkModelList = parent::getDetailViewLinks($linkParams);
		$recordId = $recordModel->getId();

		if (\App\Modules\Users\Models\Privileges::isPermitted('ProjectTask', 'EditView')) {
			$viewLinks = [
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'Add Project Task',
				'linkurl' => 'index.php?module=ProjectTask&action=EditView&projectid=' . $recordId . '&return_module=Project&return_action=DetailView&return_id=' . $recordId,
				'linkicon' => 'glyphicon glyphicon-tasks',
				'linkhint' => 'Add Project Task',
			];
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($viewLinks);
		}
		if (\App\Modules\Users\Models\Privileges::isPermitted('Documents', 'EditView')) {
			$viewLinks = [
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'Add Note',
				'linkurl' => 'index.php?module=Documents&action=EditView&return_module=Project&return_action=DetailView&return_id=' . $recordId . '&parent_id=' . $recordId,
				'linkicon' => 'glyphicon glyphicon-file',
				'linkhint' => 'Add Note',
			];
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($viewLinks);
		}
		return $linkModelList;
	}

	/**
	 * Function to get the detail view related links
	 * @return array - list of links parameters
	 */
	public function getDetailViewRelatedLinks()
	{
		$recordModel = $this->getRecord();
		$moduleName = $recordModel->getModuleName();
		$relatedLinks = parent::getDetailViewRelatedLinks();
		$parentModel = \App\Modules\Base\Models\Module::getInstance('OSSTimeControl');
		if ($parentModel->isActive()) {
			$relatedLinks[] = [
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_CHARTS', $moduleName),
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showCharts&requestMode=charts',
				'linkicon' => '',
				'linkKey' => 'LBL_RECORD_SUMMARY',
				'related' => 'Charts'
			];
		}
		if (!\App\Modules\Settings\ModuleManager\Models\Library::checkLibrary('Gantt')) {
			$relatedLinks[] = [
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_GANTT', $moduleName),
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showGantt',
				'linkicon' => '',
				'linkKey' => 'LBL_GANTT',
				'related' => 'Gantt'
			];
		}
		return $relatedLinks;
	}
}
