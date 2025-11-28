<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Exports failed staging rows for a batch.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Actions;

use App\Base\Controllers\BaseActionController;
use App\Modules\ImportManager\Services\BatchRepository;
use App\Modules\ImportManager\Services\RetryManager;

class ExportErrors extends BaseActionController
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
		$batchId = (int) $request->get('batch_id');
		$batch = $this->batches->find($batchId);
		$currentUserId = \App\Modules\Users\Models\Record::getCurrentUserId();
		if (!$batch || (int) $batch['created_by'] !== (int) $currentUserId) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$filename = sprintf('import_errors_batch_%d.csv', $batchId);
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		$fh = fopen('php://output', 'w');
		if ($fh === false) {
			throw new \RuntimeException('Nie można utworzyć strumienia wyjściowego.');
		}
		fputs($fh, "\xEF\xBB\xBF");

		$delimiter = ';';
		$this->retryManager->streamFailedRowsCsv($batchId, static function (array $row) use ($fh, $delimiter) {
			fputcsv($fh, $row, $delimiter);
		});

		fclose($fh);
		exit;
	}
}

