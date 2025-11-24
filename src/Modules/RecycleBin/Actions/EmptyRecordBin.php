<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\RecycleBin\Actions;

/**
 * EmptyRecordBin action class.
 * 
 * Permanently deletes a single record from the recycle bin.
 */
class EmptyRecordBin extends \App\Base\Controllers\BaseActionController
{
	/**
	 * Check permission to delete records from recycle bin
	 */
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		if ($sourceModule) {
			$currentUserPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
			if (!$currentUserPrivilegesModel->hasModuleActionPermission($sourceModule, 'Delete')) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
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

	/**
	 * Process the action - permanently delete a record from recycle bin
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		
		if (!$recordId) {
			throw new \App\Exceptions\AppException('No record ID provided');
		}

		$recycleBinModule = new \App\Modules\RecycleBin\Models\Module();
		$recycleBinModule->deleteRecords([$recordId]);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => true]);
		$response->emit();
	}
}

