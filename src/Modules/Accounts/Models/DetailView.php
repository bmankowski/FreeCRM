<?php

namespace App\Modules\Accounts\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com.
 * *********************************************************************************** */

class DetailView extends \App\Modules\Base\Models\DetailView
{

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param <array> $linkParams - parameters which will be used to calicaulate the params
	 * @return <array> - array of link models in the format as below
	 *                   array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams)
	{
		$recordModel = $this->getRecord();
		$linkModelList = parent::getDetailViewLinks($linkParams);
		$moduleModel = $this->getModule();

		if ($moduleModel->isPermitted('DetailTransferOwnership')) {
			$massActionLink = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_TRANSFER_OWNERSHIP',
				'linkurl' => 'javascript:Vtiger_Detail_Js.triggerTransferOwnership("index.php?module=' . $moduleModel->getName() . '&view=MassActionAjax&mode=transferOwnership")',
				'linkicon' => 'glyphicon glyphicon-user'
			);
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($massActionLink);
		}
		return $linkModelList;
	}

	public function getDetailViewRelatedLinks()
	{
		$recordModel = $this->getRecord();
		$moduleName = $recordModel->getModuleName();
		$parentModuleModel = $this->getModule();
		$this->getWidgets();
		$relatedLinks = [];

		if ($parentModuleModel->isSummaryViewSupported() && $this->widgetsList) {
			$relatedLinks = [[
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'LBL_RECORD_SUMMARY',
				'linkKey' => 'LBL_RECORD_SUMMARY',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showDetailViewByMode&requestMode=summary',
				'linkicon' => '',
				'related' => 'Summary'
			]];
		}
		//link which shows the summary information(generally detail of record)
		$relatedLinks[] = [
			'linktype' => 'DETAILVIEWTAB',
			'linklabel' => 'LBL_RECORD_DETAILS',
			'linkKey' => 'LBL_RECORD_DETAILS',
			'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showDetailViewByMode&requestMode=full',
			'linkicon' => '',
			'related' => 'Details'
		];

		if ($moduleName === 'Leads') {
			$showPSTab = (!\App\Core\AppConfig::module($moduleName, 'HIDE_SUMMARY_PRODUCTS_SERVICES')) && (\App\Utils\ModuleUtils::isModuleActive('OutsourcedProducts') || \App\Utils\ModuleUtils::isModuleActive('Products') || \App\Utils\ModuleUtils::isModuleActive('Services') || \App\Utils\ModuleUtils::isModuleActive('OSSOutsourcedServices'));
		}
		if ($moduleName === 'Accounts') {
			$showPSTab = (!\App\Core\AppConfig::module($moduleName, 'HIDE_SUMMARY_PRODUCTS_SERVICES')) && (\App\Utils\ModuleUtils::isModuleActive('OutsourcedProducts') || \App\Utils\ModuleUtils::isModuleActive('Products') || \App\Utils\ModuleUtils::isModuleActive('Services') || \App\Utils\ModuleUtils::isModuleActive('OSSOutsourcedServices') || \App\Utils\ModuleUtils::isModuleActive('Assets') || \App\Utils\ModuleUtils::isModuleActive('OSSSoldServices'));
		}
		if ('Contacts' != $moduleName && $showPSTab) {
			$relatedLinks[] = array(
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'LBL_RECORD_SUMMARY_PRODUCTS_SERVICES',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showRelatedProductsServices&requestMode=summary',
				'linkicon' => '',
				'linkKey' => 'LBL_RECORD_SUMMARY',
				'related' => 'ProductsAndServices',
				'countRelated' => \App\Core\AppConfig::relation('SHOW_RECORDS_COUNT')
			);
		}
		$modCommentsModel = \App\Modules\Base\Models\Module::getInstance('ModComments');
		if ($parentModuleModel->isCommentEnabled() && $modCommentsModel->isPermitted('DetailView')) {
			$relatedLinks[] = array(
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'ModComments',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showAllComments',
				'linkicon' => '',
				'related' => 'Comments',
				'countRelated' => \App\Core\AppConfig::relation('SHOW_RECORDS_COUNT')
			);
		}

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
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if ($userPrivilegesModel->hasModulePermission('OpenStreetMap')) {
			$relatedLinks[] = [
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'LBL_MAP',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showOpenStreetMap',
				'linkicon' => '',
			];
		}
		$relationModels = $parentModuleModel->getRelations();

		foreach ($relationModels as $relation) {
			$link = array(
				'linktype' => 'DETAILVIEWRELATED',
				'linklabel' => $relation->get('label'),
				'linkurl' => $relation->getListUrl($recordModel),
				'linkicon' => '',
				'relatedModuleName' => $relation->get('relatedModuleName')
			);
			$relatedLinks[] = $link;
		}

		return $relatedLinks;
	}
}
