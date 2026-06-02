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

class DetailView extends \App\Modules\Base\Models\DetailView
{

	/**
	 * Function to get the detail view related links
	 * @return array - list of links parameters
	 */
	public function getDetailViewRelatedLinks()
	{
		$recordModel = $this->getRecord();
		$moduleName = $recordModel->getType();
		$relatedLinks = array();
		//link which shows the summary information(generally detail of record)
		$relatedLinks[] = array(
			'linktype' => 'DETAILVIEWTAB',
			'linklabel' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_DETAILS', $moduleName),
			'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showDetailViewByMode&requestMode=full',
			'linkicon' => '',
			'linkKey' => 'LBL_RECORD_DETAILS',
			'related' => 'Details'
		);

		$parentModuleModel = $this->getModule();
		if ($parentModuleModel->isTrackingEnabled()) {
			$relatedLinks[] = [
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'LBL_UPDATES',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showRecentActivities&page=1',
				'linkicon' => '',
				'related' => 'Updates',
				'countRelated' => \App\Core\AppConfig::module('ModTracker', 'UNREVIEWED_COUNT') && $parentModuleModel->isPermitted('ReviewingUpdates'),
				'badgeClass' => 'bgDanger'
			];
		}
		return $relatedLinks;
	}

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param array $linkParams - parameters which will be used to calicaulate the params
	 * @return array - array of link models in the format as below
	 *                  array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams)
	{
		$currentUserModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();

		$linkModelList = parent::getDetailViewLinks($linkParams);
		$recordModel = $this->getRecord();
		$moduleName = $recordModel->getModuleName();
		$recordId = $recordModel->getId();
		$status = $recordModel->get('activitystatus');
		$statusActivity = \App\Modules\Calendar\Models\Module::getComponentActivityStateLabel('current');

		if ($recordModel->isEditable() && $this->getModule()->isPermitted('DetailView') && \App\Security\Privilege::isPermitted($moduleName, 'ActivityComplete', $recordId) && \App\Security\Privilege::isPermitted($moduleName, 'ActivityCancel', $recordId) && \App\Security\Privilege::isPermitted($moduleName, 'ActivityPostponed', $recordId) && in_array($status, $statusActivity)) {
			$basicActionLink = [
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_SET_RECORD_STATUS',
				'linkurl' => '#',
				'linkdata' => ['url' => $recordModel->getActivityStateModalUrl()],
				'linkicon' => 'glyphicon glyphicon-ok',
				'linkclass' => 'showModal closeCalendarRekord'
			];
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($basicActionLink);
		}
		if (\App\Security\Privilege::isPermitted('OpenStreetMap') && !$recordModel->isEmpty('location')) {
			$basicActionLink = [
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_SHOW_LOCATION',
				'linkurl' => 'javascript:Vtiger_Index_Js.showLocation(\'' . $recordModel->get('location') . '\')',
				'linkicon' => 'glyphicon glyphicon-map-marker',
			];
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($basicActionLink);
		}

		if ($recordModel->isDeletable() && $recordModel->get('reapeat') === 1) {
			foreach ($linkModelList['DETAILVIEW'] as $key => $linkObject) {
				if ($linkObject->linklabel == 'LBL_DELETE_RECORD') {
					unset($linkModelList['DETAILVIEW'][$key]);
				}
			}
			$deletelinkModel = [
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_DELETE_RECORD',
				'linkurl' => 'javascript:Calendar_Detail_Js.deleteRecord("' . $recordModel->getDeleteUrl() . '")',
				'linkicon' => 'glyphicon glyphicon-trash',
				'title' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_DELETE_RECORD')
			];
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($deletelinkModel);
		}
		return $linkModelList;
	}
}
