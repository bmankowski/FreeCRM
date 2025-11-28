<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * AJAX action that triggers staging step for ImportManager batches.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchProcessor;
use App\Modules\ImportManager\Services\BatchRepository;

class Stage extends BaseActionController
{
	private BatchProcessor $processor;
	private BatchRepository $batches;

	public function __construct()
	{
		parent::__construct();
		$this->processor = new BatchProcessor();
		$this->batches = new BatchRepository();
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$batchId = (int) $request->get('batch_id');
		if ($batchId <= 0) {
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
				throw new \RuntimeException('Nie masz dostępu do tego wsadu.');
			}
			$result = $this->processor->stage($batchId);
			$response->setResult($result);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager staging failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}
}

