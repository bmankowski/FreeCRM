<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * AJAX action triggering final import for ImportManager batches.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchProcessor;
use App\Modules\ImportManager\Services\BatchRepository;
use App\Modules\ImportManager\Services\QueueDispatcher;

class Import extends BaseActionController
{
	private BatchProcessor $processor;
	private BatchRepository $batches;
	private QueueDispatcher $queue;

	public function __construct()
	{
		parent::__construct();
		$this->processor = new BatchProcessor();
		$this->batches = new BatchRepository();
		$this->queue = new QueueDispatcher();
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
			$currentUserId = $request->getUserId();
			if (!$batch || (int) $batch['created_by'] !== (int) $currentUserId) {
				throw new \RuntimeException('Nie masz dostępu do tego wsadu.');
			}

			$runMode = $this->sanitizeRunMode($request->get('run_mode'));
			if ($runMode === 'queue') {
				$job = $this->queue->enqueueImport($batch);
				$response->setResult([
					'queued' => true,
					'jobId' => $job->getId(),
				]);
				$response->emit();
				return;
			}

			if ($runMode !== 'inline' && $this->queue->shouldEnqueue($batch)) {
				$job = $this->queue->enqueueImport($batch);
				$response->setResult([
					'queued' => true,
					'jobId' => $job->getId(),
				]);
				$response->emit();
				return;
			}

			$result = $this->processor->import($batchId);
			$response->setResult([
				'queued' => false,
				'result' => $result,
			]);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager final import failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}

	private function sanitizeRunMode($value): string
	{
		if (!is_string($value)) {
			return '';
		}
		$mode = strtolower(trim($value));
		return in_array($mode, ['inline', 'queue'], true) ? $mode : '';
	}
}

