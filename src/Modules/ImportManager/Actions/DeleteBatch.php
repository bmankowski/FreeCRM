<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Deletes an ImportManager batch owned by the current user.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchCleanupService;

class DeleteBatch extends BaseActionController
{
	private BatchCleanupService $cleanup;

	public function __construct()
	{
		parent::__construct();
		$this->cleanup = new BatchCleanupService();
	}

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

		try {
			$batchId = (int) $request->get('batch_id');
			if ($batchId <= 0) {
				throw new \RuntimeException('Brak poprawnego identyfikatora wsadu.');
			}

			$userId = (int) ($request->getUserId() ?? \App\User\CurrentUser::getId() ?? 0);
			if ($userId <= 0) {
				throw new \RuntimeException('Brak aktywnej sesji użytkownika.');
			}

			$this->cleanup->deleteBatch($batchId, $userId);
			$response->setResult(['batchId' => $batchId]);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager DeleteBatch failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}
}
