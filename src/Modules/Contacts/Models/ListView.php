<?php

namespace App\Modules\Contacts\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class ListView extends \App\Modules\Base\Models\ListView
{

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  \App\Modules\Base\Models\Link instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams, ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		$links = parent::getListViewMassActions($linkParams, $currentUser);
		$moduleModel = $this->getModule();
		$currentUserModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$massActionLinks = [];
		if ($moduleModel->isPermitted('MassComposeEmail') && \App\Core\AppConfig::main('isActiveSendingMails') && \App\Email\Mail::getDefaultSmtp()) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_MASS_SEND_EMAIL',
				'linkurl' => 'javascript:Vtiger_ListView_Js.triggerSendEmail();',
				'linkicon' => ''
			);
		}
		if ($currentUserModel->hasModulePermission('SMSNotifier') && $moduleModel->isPermitted('MassSendSMS')) {
			$massActionLinks[] = array(
				'linktype' => 'LISTVIEWMASSACTION',
				'linklabel' => 'LBL_MASS_SEND_SMS',
				'linkurl' => 'javascript:Vtiger_ListView_Js.triggerSendSms("index.php?module=' . $this->getModule()->getName() . '&view=MassActionAjax&mode=showSendSMSForm","SMSNotifier");',
				'linkicon' => ''
			);
		}
		foreach ($massActionLinks as $massActionLink) {
			$links['LISTVIEWMASSACTION'][] = \App\Modules\Base\Models\Link::getInstanceFromValues($massActionLink);
		}
		return $links;
	}

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of \App\Modules\Base\Models\Link instances
	 */
	public function getListViewLinks($linkParams, ?\App\Modules\Users\Models\Record $currentUser = null)
	{
		$links = parent::getListViewLinks($linkParams, $currentUser);

		$index = 0;
		foreach ($links['LISTVIEWBASIC'] as $link) {
			if ($link->linklabel == 'Send SMS') {
				unset($links['LISTVIEWBASIC'][$index]);
			}
			$index++;
		}
		return $links;
	}
}
