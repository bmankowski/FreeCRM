<?php

namespace App\Modules\RecycleBin\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class RecycleBinAjax extends \App\Base\Controllers\BaseActionController
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('restoreRecords');
		$this->exposeMethod('emptyRecycleBin');
		$this->exposeMethod('deleteRecords');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if ($request->get('mode') == 'emptyRecycleBin') {
			//we dont check for permissions since recylebin axis will not be there for non admin users
			return true;
		}
		$targetModuleName = $request->get('sourceModule', $request->get('module'));
		$currentUserPriviligesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$currentUserPriviligesModel->hasModuleActionPermission($targetModuleName, 'Delete')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');

		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Get records list from request, handling JSON strings and arrays
	 * @param \App\Http\Vtiger_Request $request
	 * @return array|null
	 */
	private function getRecordsListFromRequest(\App\Http\Vtiger_Request $request)
	{
		// First check if selected_ids is directly provided (for single record actions)
		$selectedIds = $request->get('selected_ids');
		
		// If selected_ids is already an array and not empty, use it directly
		if (is_array($selectedIds) && !empty($selectedIds)) {
			return $selectedIds;
		}
		
		// If selected_ids is a string, try to parse it as JSON
		if (is_string($selectedIds) && !empty($selectedIds)) {
			$trimmed = trim($selectedIds);
			// Check if it looks like JSON array or object
			if ((strpos($trimmed, '[') === 0 || strpos($trimmed, '{') === 0)) {
				$decoded = json_decode($trimmed, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
					return $decoded;
				}
			}
		}
		
		// Also check raw value in case Vtiger_Request didn't parse it correctly
		$rawSelectedIds = $request->getRaw('selected_ids');
		if (is_string($rawSelectedIds) && !empty($rawSelectedIds) && $rawSelectedIds !== $selectedIds) {
			$trimmed = trim($rawSelectedIds);
			if ((strpos($trimmed, '[') === 0 || strpos($trimmed, '{') === 0)) {
				$decoded = json_decode($trimmed, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
					return $decoded;
				}
			}
		}
		
		// For mass actions, try to use Mass::getRecordsListFromRequest (uses viewname to get selected records)
		$result = \App\Modules\Base\Actions\Mass::getRecordsListFromRequest($request);
		
		// If result is an array, return it
		if (is_array($result) && !empty($result)) {
			return $result;
		}
		
		// If result is a string, try to parse it as JSON
		if (is_string($result) && !empty($result)) {
			$trimmed = trim($result);
			if ((strpos($trimmed, '[') === 0 || strpos($trimmed, '{') === 0)) {
				$decoded = json_decode($trimmed, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded) && !empty($decoded)) {
					return $decoded;
				}
			}
		}
		
		// If nothing worked, return null
		return null;
	}

	/**
	 * Function to restore the deleted records.
	 * @param type $sourceModule
	 * @param type $recordIds
	 */
	public function restoreRecords(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		$recordIds = $this->getRecordsListFromRequest($request);
		
		$recycleBinModule = new \App\Modules\RecycleBin\Models\Module();

		$response = new \App\Http\Vtiger_Response();
		if ($recordIds && is_array($recordIds) && count($recordIds) > 0) {
			$recycleBinModule->restore($sourceModule, $recordIds);
			$response->setResult(array(true));
		} else {
			// Return error message
			$errorMsg = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NO_RECORD_SELECTED', 'Vtiger');
			$response->setError($errorMsg);
		}

		$response->emit();
	}

	/**
	 * Function to delete the records permanently in vitger CRM database
	 */
	public function emptyRecycleBin(\App\Http\Vtiger_Request $request)
	{
		$recycleBinModule = new \App\Modules\RecycleBin\Models\Module();

		$status = $recycleBinModule->emptyRecycleBin();

		if ($status) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(array($status));
			$response->emit();
		}
	}

	/**
	 * Function to deleted the records permanently in CRM
	 * @param type $reocrdIds
	 */
	public function deleteRecords(\App\Http\Vtiger_Request $request)
	{
		$recordIds = $this->getRecordsListFromRequest($request);
		
		$recycleBinModule = new \App\Modules\RecycleBin\Models\Module();

		$response = new \App\Http\Vtiger_Response();
		if ($recordIds && is_array($recordIds) && count($recordIds) > 0) {
			$recycleBinModule->deleteRecords($recordIds);
			$response->setResult(array(true));
		} else {
			$errorMsg = \App\Runtime\Vtiger_Language_Handler::translate('LBL_NO_RECORD_SELECTED', 'Vtiger');
			$response->setError($errorMsg);
		}
		$response->emit();
	}
}
