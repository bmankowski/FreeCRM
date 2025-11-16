<?php

namespace App\Modules\Cron\Runner;

use Exception;

/**
 * Cron Runner - executes scheduled cron tasks
 * @package App\Modules\Cron\Runner
 */
class CronRunner
{
	/**
	 * Run cron tasks
	 * @param string|null $serviceName Specific service to run, or null to run all
	 * @return void
	 */
	public function run(?string $serviceName = null): void
	{
		$cronTasks = false;
		\vtlib\Cron::setCronAction(true);
		
		if ($serviceName !== null) {
			// Run specific service
			$cronTask = \vtlib\Cron::getInstance($serviceName);
			if ($cronTask === null) {
				echo sprintf('ERROR: Cron task "%s" not found' . PHP_EOL, $serviceName);
				return;
			}
			$cronTasks = [$cronTask];
		} else {
			// Run all services
			$cronTasks = \vtlib\Cron::listAllActiveInstances();
		}

		$cronStart = microtime(true);
		
		// Set current user permissions for cron context
		$adminId = \App\Modules\Users\Models\Record::getActiveAdminId();
		\App\Modules\Users\Models\Record::setCurrentUserId($adminId);
		
		if (PHP_SAPI !== 'cli') {
			echo '<pre>';
		}
		
		echo sprintf('---------------  %s | Start CRON  ----------', date('Y-m-d H:i:s')) . PHP_EOL;
		
		foreach ($cronTasks as $cronTask) {
			$this->runTask($cronTask);
		}
		
		echo sprintf('===============  %s (' . round(microtime(true) - $cronStart, 2) . ') | End CRON  ==========', date('Y-m-d H:i:s')) . PHP_EOL;
	}

	/**
	 * Run a single cron task
	 * @param \vtlib\Cron $cronTask
	 * @return void
	 */
	public function runTask(\vtlib\Cron $cronTask): void
	{
		try {
			\App\Log::trace($cronTask->getName() . ' - Start');
			
			// Timeout could happen if intermediate cron-tasks fails
			// and affect the next task. Which need to be handled in this cycle.
			if ($cronTask->hadTimeout()) {
				echo sprintf('%s | %s - Cron task had timedout as it was not completed last time it run' . PHP_EOL, date('Y-m-d H:i:s'), $cronTask->getName());
				if (\App\AppConfig::main('unblockedTimeoutCronTasks')) {
					$cronTask->unlockTask();
				}
			}

			// Not ready to run yet?
			if ($cronTask->isRunning()) {
				\App\Log::trace($cronTask->getName() . ' - Task omitted, it has not been finished during the last scanning');
				echo sprintf('%s | %s - Task omitted, it has not been finished during the last scanning' . PHP_EOL, date('Y-m-d H:i:s'), $cronTask->getName());
				return;
			}

			// Not ready to run yet?
			if (!$cronTask->isRunnable()) {
				\App\Log::trace($cronTask->getName() . ' - Not ready to run as the time to run again is not completed');
				echo sprintf('%s | %s - Not ready to run as the time to run again is not completed' . PHP_EOL, date('Y-m-d H:i:s'), $cronTask->getName());
				return;
			}

			// Mark the status - running
			$cronTask->markRunning();
			echo sprintf('%s | %s - Start task' . PHP_EOL, date('Y-m-d H:i:s'), $cronTask->getName());
			$startTime = microtime(true);

			\vtlib\Deprecated::checkFileAccess($cronTask->getHandlerFile());
			ob_start();
			require_once $cronTask->getHandlerFile();
			$taskResponse = ob_get_contents();
			ob_end_clean();

			$taskTime = round(microtime(true) - $startTime, 2);
			if ($taskResponse != '') {
				\App\Log::warning($cronTask->getName() . ' - The task returned a message:' . PHP_EOL . $taskResponse);
				echo 'Task response:' . PHP_EOL . $taskResponse . PHP_EOL;
			}

			// Mark the status - finished
			$cronTask->markFinished();
			echo sprintf('%s | %s - End task (%s s)', date('Y-m-d H:i:s'), $cronTask->getName(), $taskTime) . PHP_EOL;
			\App\Log::trace($cronTask->getName() . ' - End');
		} catch (\App\Exceptions\AppException $e) {
			echo sprintf('%s | ERROR: %s - Cron task execution throwed exception.', date('Y-m-d H:i:s'), $cronTask->getName()) . PHP_EOL;
			echo $e->getMessage() . PHP_EOL;
			echo $e->getTraceAsString() . PHP_EOL;
			if (\App\AppConfig::main('systemMode') === 'test') {
				throw $e;
			}
		}
	}
}

