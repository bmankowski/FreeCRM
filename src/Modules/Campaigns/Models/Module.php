<?php

namespace App\Modules\Campaigns\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Module extends \App\Modules\Base\Models\Module
{

	/**
	 * Function to get Specific Relation Query for this Module
	 * @param mixed $relatedModule
	 * @return mixed
	 */
	public function getSpecificRelationQuery($relatedModule)
	{
		if ($relatedModule === 'Leads') {
			$specificQuery = 'AND vtiger_leaddetails.converted = 0';
			return $specificQuery;
		}
		return parent::getSpecificRelationQuery($relatedModule);
	}

	/**
	 * Function to get list view query for popup window
	 * @param string $sourceModule Parent module
	 * @param string $field parent fieldname
	 * @param string $record parent id
	 * @param \App\QueryField\QueryGenerator $queryGenerator
	 */
	public function getQueryByModuleField($sourceModule, $field, $record, \App\QueryField\QueryGenerator $queryGenerator)
	{
		if (in_array($sourceModule, array('Accounts', 'Leads', 'Vendors', 'Contacts', 'Partners', 'Competition'))) {
			$subQuery = (new \App\Db\Query())->select(['campaignid'])->from('vtiger_campaign_records')->where(['crmid' => $record]);
			$queryGenerator->addNativeCondition(['not in', 'vtiger_campaign.campaignid', $subQuery]);
		}
	}
}
