<?php

namespace App\Modules\Candidates\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce S.A.
 * *********************************************************************************** */

class Relation extends \App\Modules\Base\Models\Relation
{
	/**
	 * Get related members (ProjektyRekrutacyjne) for Candidates.
	 * Uses custom relation table u_#__projekty_rekrutacyjne_relations_members_entity
	 */
	public function getRelatedMembers()
	{
		$tableName = 'u_#__projekty_rekrutacyjne_relations_members_entity';
		$queryGenerator = $this->getQueryGenerator();
		$record = $this->get('parentRecord')->getId();
		
		// Add custom fields from relation table
		$customFields = [
			'recruitment_status_rel',
			'comment_rel',
			'rel_created_time',
			'rel_created_user'
		];
		
		foreach ($customFields as $fieldName) {
			$queryGenerator->setCustomColumn([$fieldName => "{$tableName}.{$fieldName}"]);
		}
		
		// Directional relation for this view:
		// parent candidate is stored in relcrmid, related project in crmid.
		$queryGenerator->addJoin(['INNER JOIN', $tableName, "{$tableName}.crmid = vtiger_crmentity.crmid"]);
		$queryGenerator->addNativeCondition(["{$tableName}.relcrmid" => $record]);
	}
}

