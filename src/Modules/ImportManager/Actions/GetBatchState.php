<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Returns batch state for restoring wizard view.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchRepository;

class GetBatchState extends BaseActionController
{
	private BatchRepository $batches;

	public function __construct()
	{
		parent::__construct();
		$this->batches = new BatchRepository();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		if ((int) $request->get('batch_id') <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$response = new \App\Http\Vtiger_Response();

		try {
			$batchId = (int) $request->get('batch_id');
			$batch = $this->batches->find($batchId);
			$currentUserId = \App\Modules\Users\Models\Record::getCurrentUserId();
			
			if (!$batch || (int) $batch['created_by'] !== (int) $currentUserId) {
				throw new \RuntimeException('Brak dostępu do wskazanego wsadu.');
			}

			$response->setResult([
				'module' => $batch['module'],
				'status' => $batch['status'],
				'file_name' => $batch['file_name'] ?? null,
				'duplicate_strategy' => $batch['duplicate_strategy'] ?? null,
				'total_rows' => isset($batch['total_rows']) ? (int) $batch['total_rows'] : null,
				'error_rows' => isset($batch['error_rows']) ? (int) $batch['error_rows'] : null,
				'processed_rows' => isset($batch['processed_rows']) ? (int) $batch['processed_rows'] : null,
			]);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager GetBatchState failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}
}

