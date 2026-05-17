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
use App\Modules\ImportManager\Services\QueueDispatcher;

class Stage extends BaseActionController
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

			$runMode = '';
			$runModeRaw = $request->get('run_mode');
			if (is_string($runModeRaw)) {
				$runModeCandidate = strtolower(trim($runModeRaw));
				if (in_array($runModeCandidate, ['inline', 'queue'], true)) {
					$runMode = $runModeCandidate;
				}
			}
			if ($runMode === 'queue') {
				$job = $this->queue->enqueueStage($batch);
				$response->setResult([
					'queued' => true,
					'jobId' => $job->getId(),
				]);
				$response->emit();
				return;
			}

			if ($runMode !== 'inline' && $this->queue->shouldEnqueue($batch)) {
				$job = $this->queue->enqueueStage($batch);
				$response->setResult([
					'queued' => true,
					'jobId' => $job->getId(),
				]);
				$response->emit();
				return;
			}

			$result = $this->processor->stage($batchId);
			$response->setResult([
				'queued' => false,
				'result' => $result,
			]);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager staging failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}
}

