<?php
namespace App\Modules\Kandydaci;
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class Kandydaci extends \App\Core\CRMEntity
{
	public $table_name = 'u_yf_kandydaci';
	public $table_index = 'kandydaciid';

	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = ['u_yf_kandydacicf', 'kandydaciid'];

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	public $tab_name = ['vtiger_crmentity', 'u_yf_kandydaci', 'u_yf_kandydacicf'];

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	public $tab_name_index = [
		'vtiger_crmentity' => 'crmid',
		'u_yf_kandydaci' => 'kandydaciid',
		'u_yf_kandydacicf' => 'kandydaciid',
	];

	/** Default fields on the list */
	public $list_fields_name = [
		'Nazwisko i imię' => 'name',
		'Assigned To' => 'assigned_user_id',
	];

	// For Popup listview and UI type support
	public $search_fields = [
		// Format: Field Label => Array(tablename, columnname)
		'Nazwisko i imię' => ['kandydaci', 'name'],
		'Assigned To' => ['vtiger_crmentity', 'assigned_user_id'],
	];
	public $search_fields_name = [
		'Nazwisko i imię' => 'name',
		'Status'          => 'status_kandydata',
		'Telefon'         => 'telefon',
		'E-mail'          => 'email_prywatny',
		'Assigned To'     => 'assigned_user_id',
	];
	// For Popup window record selection
	public $popup_fields = ['name'];
	// For Alphabetical search
	public $def_basicsearch_col = 'name';
	// Column value to use on detail view record text display
	public $def_detailview_recname = 'name';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = ['name', 'assigned_user_id'];
	public $default_order_by = '';
	public $default_sort_order = 'ASC';

	/**
	 * Save related module - handles M:M relation with ProjektyRekrutacyjne
	 * 
	 * @param string $module This module name
	 * @param integer $crmid This module record number
	 * @param string $withModule Related module name
	 * @param mixed $withCrmid Integer or Array of related module record number
	 * @param string $relatedName Function name
	 */
	public function save_related_module($module, $crmid, $withModule, $withCrmid, $relatedName = false)
	{
		if (!is_array($withCrmid)) {
			$withCrmid = [$withCrmid];
		}

		// Handle ProjektyRekrutacyjne relation using custom M:M table
		if ($withModule === 'ProjektyRekrutacyjne' && $relatedName === 'getRelatedMembers') {
			$typeRelationModel = new \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers();
			foreach ($withCrmid as $projectId) {
				$typeRelationModel->createMembership(
					(int) $projectId,
					(int) $crmid,
					\App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers::STATUS_MANUALLY_ADDED
				);
			}
		} else {
			// Default handling for other relations
			parent::save_related_module($module, $crmid, $withModule, $withCrmid, $relatedName);
		}
	}

	/**
	 * Delete related module - handles M:M relation with ProjektyRekrutacyjne
	 * 
	 * @param string $module This module name
	 * @param integer $crmid This module record number
	 * @param string $withModule Related module name
	 * @param mixed $withCrmid Integer or Array of related module record number
	 */
	public function delete_related_module($module, $crmid, $withModule, $withCrmid)
	{
		if (!is_array($withCrmid)) {
			$withCrmid = [$withCrmid];
		}

		// Handle ProjektyRekrutacyjne relation using custom M:M table
		if ($withModule === 'ProjektyRekrutacyjne') {
			$typeRelationModel = new \App\Modules\ProjektyRekrutacyjne\Relations\GetRelatedMembers();
			foreach ($withCrmid as $projectId) {
				$typeRelationModel->delete((int) $projectId, (int) $crmid);
			}
		} else {
			// Default handling for other relations
			parent::delete_related_module($module, $crmid, $withModule, $withCrmid);
		}
	}

	/**
	 * Invoked when special actions are performed on the module.
	 *
	 * @param string $moduleName Module name
	 * @param string $eventType  Event Type
	 */
	public function moduleHandler($moduleName, $eventType)
	{
		if ('module.postinstall' === $eventType) {
		} elseif ('module.disabled' === $eventType) {
		} elseif ('module.preuninstall' === $eventType) {
		} elseif ('module.preupdate' === $eventType) {
		} elseif ('module.postupdate' === $eventType) {
		}
	}
}
