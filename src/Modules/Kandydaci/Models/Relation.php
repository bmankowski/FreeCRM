<?php

namespace App\Modules\Kandydaci\Models;

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
	 * Get related members (ProjektyRekrutacyjne) for Kandydaci
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
		
		// Join relation table - relation is bidirectional
		// When Kandydaci is parent, we need to find ProjektyRekrutacyjne where:
		// - relcrmid = kandydaci_id (Kandydaci is related to project)
		// - OR crmid = kandydaci_id (Kandydaci is the project itself - less common)
		$queryGenerator->addJoin(['INNER JOIN', $tableName, "({$tableName}.relcrmid = vtiger_crmentity.crmid OR {$tableName}.crmid = vtiger_crmentity.crmid)"]);
		$queryGenerator->addNativeCondition([
			'or',
			["{$tableName}.relcrmid" => $record],
			["{$tableName}.crmid" => $record]
		]);
		
		// Ensure we only get ProjektyRekrutacyjne records (not Kandydaci)
		// The join condition already filters by the relation table
	}
}

