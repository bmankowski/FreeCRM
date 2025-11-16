<?php

namespace App\Modules\HelpDesk\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
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
		$linkModelList = parent::getDetailViewLinks($linkParams);
		$recordModel = $this->getRecord();

		$quotesModuleModel = \App\Modules\Base\Models\Module::getInstance('Faq');
		if ($quotesModuleModel->isPermitted('DetailView')) {
			$basicActionLink = array(
				'linktype' => 'DETAILVIEW',
				'linklabel' => 'LBL_CONVERT_FAQ',
				'linkurl' => $recordModel->getConvertFAQUrl(),
				'showLabel' => 1,
			);
			$linkModelList['DETAILVIEW'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($basicActionLink);
		}

		return $linkModelList;
	}

	public function getDetailViewRelatedLinks()
	{
		$recordModel = $this->getRecord();
		$moduleName = $recordModel->getModuleName();

		$relatedLinks = parent::getDetailViewRelatedLinks();
		$parentModel = \App\Modules\Base\Models\Module::getInstance('OSSTimeControl');
		if ($parentModel->isActive()) {
			$relatedLinks[] = [
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'LBL_CHARTS',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showCharts&requestMode=charts',
				'linkicon' => '',
				'linkKey' => 'LBL_RECORD_SUMMARY',
				'related' => 'Charts'
			];
		}
		$showPSTab = (!\App\Core\AppConfig::module($moduleName, 'HIDE_SUMMARY_PRODUCTS_SERVICES')) && (\App\Utils\ModuleUtils::isModuleActive('Products') || \App\Utils\ModuleUtils::isModuleActive('Services') || \App\Utils\ModuleUtils::isModuleActive('Assets') || \App\Utils\ModuleUtils::isModuleActive('OSSSoldServices'));
		if ($showPSTab) {
			$relatedLinks[] = [
				'linktype' => 'DETAILVIEWTAB',
				'linklabel' => 'LBL_RECORD_SUMMARY_PRODUCTS_SERVICES',
				'linkurl' => $recordModel->getDetailViewUrl() . '&mode=showRelatedProductsServices&requestMode=summary',
				'linkicon' => '',
				'linkKey' => 'LBL_RECORD_SUMMARY',
				'related' => 'ProductsAndServices',
				'countRelated' => \App\Core\AppConfig::relation('SHOW_RECORDS_COUNT')
			];
		}
		return $relatedLinks;
	}
}
