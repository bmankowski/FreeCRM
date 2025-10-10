<?php

namespace FreeCRM\Modules\Campaigns\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class RelationListView extends \FreeCRM\Modules\Vtiger\Models\Model
{

	/**
	 * Function to get the links for related list
	 * @return <Array> List of action models <\FreeCRM\Modules\Vtiger\Models\Link>
	 */
	public function getLinks()
	{
		$relatedLinks = parent::getLinks();
		$relationModel = $this->getRelationModel();
		$relatedModuleModel = $relationModel->getRelationModuleModel();
		$relatedModuleName = $relatedModuleModel->getName();
		if (in_array($relatedModuleName, ['Accounts', 'Leads', 'Vendors', 'Contacts', 'Partners', 'Competition'])) {
			if ($relatedModuleModel->isPermitted('MassComposeEmail') && \FreeCRM\AppConfig::main('isActiveSendingMails') && \App\Mail::getDefaultSmtp()) {
				$emailLink = \FreeCRM\Modules\Vtiger\Models\Link::getInstanceFromValues(array(
						'linktype' => 'LISTVIEWBASIC',
						'linklabel' => vtranslate('LBL_SEND_EMAIL', $relatedModuleName),
						'linkurl' => "javascript:Campaigns_RelatedList_Js.triggerSendEmail();",
						'linkicon' => ''
				));
				$emailLink->set('_sendEmail', true);
				$relatedLinks['LISTVIEWBASIC'][] = $emailLink;
			}
		}
		return $relatedLinks;
	}
}
