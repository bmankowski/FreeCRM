<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Executes queued ImportManager jobs.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Jobs;

use App\Modules\ImportManager\Services\BatchProcessor;
use App\Modules\ImportManager\Services\QueueDispatcher;

class ImportJobProcessor
{
	private BatchProcessor $batchProcessor;
	private QueueDispatcher $dispatcher;

	public function __construct(?BatchProcessor $batchProcessor = null, ?QueueDispatcher $dispatcher = null)
	{
		$this->batchProcessor = $batchProcessor ?? new BatchProcessor();
		$this->dispatcher = $dispatcher ?? new QueueDispatcher();
	}

	public function process(ImportJob $job): void
	{
		switch ($job->getType()) {
			case 'stage':
				$this->batchProcessor->stage($job->getBatchId());
				break;
			default:
				throw new \RuntimeException('Nieobsługiwany typ zadania: ' . $job->getType());
		}
	}
}

