<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Saves inline edits of failed staging rows.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchRepository;
use App\Modules\ImportManager\Services\RetryManager;

class RetryUpdate extends BaseActionController
{
	private RetryManager $retryManager;
	private BatchRepository $batches;

	public function __construct()
	{
		parent::__construct();
		$this->retryManager = new RetryManager();
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

			if ($batch['status'] === 'running') {
				throw new \RuntimeException('Nie można edytować wsadu w trakcie przetwarzania.');
			}

			$rows = $request->get('rows');
			if (is_string($rows)) {
				$rows = \App\Utils\Json::decode($rows);
			}
			if (!is_array($rows)) {
				throw new \RuntimeException('Nie przesłano zmian do zapisania.');
			}

			$updated = $this->retryManager->updateRows($batchId, $rows);
			$response->setResult([
				'updated' => $updated,
			]);
		} catch (\Throwable $exception) {
			\App\Log\Log::error('ImportManager retry update failed: ' . $exception->getMessage(), 'ImportManager');
			$response->setError(500, $exception->getMessage());
		}

		$response->emit();
	}
}

