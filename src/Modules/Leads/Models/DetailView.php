<?php

namespace App\Modules\Leads\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com.
 * *********************************************************************************** */

class DetailView extends \App\Modules\Vtiger\Models\DetailView
{

	/**
	 * Function to get the detail view links (links and widgets)
	 * @param <array> $linkParams - parameters which will be used to calicaulate the params
	 * @return <array> - array of link models in the format as below
	 *                   array('linktype'=>list of link models);
	 */
	public function getDetailViewLinks($linkParams)
	{
		$linkModelList = \App\Modules\Vtiger\Models\DetailView::getDetailViewLinks($linkParams);
		$recordModel = $this->getRecord();
		$moduleModel = $this->getModule();
		$moduleName = $moduleModel->getName();
		$recordId = $recordModel->getId();

		$index = 0;
		foreach ($linkModelList['DETAILVIEW'] as $link) {
			if ($link->linklabel == 'View History' || $link->linklabel == 'Send SMS') {
				unset($linkModelList['DETAILVIEW'][$index]);
			} else if ($link->linklabel == 'LBL_SHOW_ACCOUNT_HIERARCHY') {
				$link->linklabel = 'LBL_SHOW_ACCOUNT_HIERARCHY';
				$linkURL = 'index.php?module=Accounts&view=AccountHierarchy&record=' . $recordId;
				$link->linkurl = 'javascript:Accounts_Detail_Js.triggerAccountHierarchy("' . $linkURL . '");';
				unset($linkModelList['DETAILVIEW'][$index]);
				$linkModelList['DETAILVIEW'][$index] = $link;
			}
			$index++;
		}

		if (\App\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'ConvertLead', $recordModel->getId()) && \App\Modules\Users\Models\Privileges::isPermitted($moduleModel->getName(), 'EditView', $recordModel->getId())) {
			$convert = !\App\Modules\Leads\Models\Module::checkIfAllowedToConvert($recordModel->get('leadstatus')) ? 'hide' : '';
			$basicActionLink = array(
				'linktype' => 'DETAILVIEWBASIC',
				'linklabel' => '',
				'linkclass' => 'btn-info btn-convertLead ' . $convert,
				'linkhint' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_CONVERT_LEAD', $moduleName),
				'linkurl' => 'javascript:Leads_Detail_Js.convertLead("' . $recordModel->getConvertLeadUrl() . '",this);',
				'linkicon' => 'glyphicon glyphicon-transfer',
			);
			$linkModelList['DETAILVIEWBASIC'][] = \App\Modules\Vtiger\Models\Link::getInstanceFromValues($basicActionLink);
		}
		return $linkModelList;
	}
}
