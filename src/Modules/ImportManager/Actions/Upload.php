<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * AJAX action handling uploads for ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Modules\ImportManager\Controllers\WizardController;

class Upload extends \App\Base\Controllers\BaseActionController
{
	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userPrivileges = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$userPrivileges || !$userPrivileges->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$response = new \App\Http\Vtiger_Response();
		$controller = new WizardController();

		try {
			$result = $controller->handleUpload($request);
			$response->setResult($result);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager upload failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}
}

