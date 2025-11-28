<?php

namespace App\Modules\ImportManager\cron;

use App\Modules\ImportManager\Jobs\ImportJob;
use App\Modules\ImportManager\Jobs\ImportJobProcessor;
use App\Modules\ImportManager\Services\QueueDispatcher;

$dispatcher = new QueueDispatcher();
$processor = new ImportJobProcessor();
$jobs = $dispatcher->fetchPendingJobs(3);

foreach ($jobs as $job) {
	try {
		$dispatcher->markStatus($job->getId(), ImportJob::STATUS_RUNNING);
		$processor->process($job);
		$dispatcher->markStatus($job->getId(), ImportJob::STATUS_COMPLETED);
	} catch (\Throwable $exception) {
		$dispatcher->markStatus($job->getId(), ImportJob::STATUS_HALTED);
		\App\Log\Log::error('ImportManager queue job failed: ' . $exception->getMessage(), 'ImportManager');
	}
}

