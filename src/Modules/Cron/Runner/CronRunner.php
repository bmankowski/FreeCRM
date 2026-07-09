<?php

namespace App\Modules\Cron\Runner;

use App\Modules\Cron\Contract\CronTaskInterface;
use Throwable;

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

		$explicitService = $serviceName !== null;
		foreach ($cronTasks as $cronTask) {
			$this->runTask($cronTask, $explicitService);
		}
		
		echo sprintf('===============  %s (' . round(microtime(true) - $cronStart, 2) . ') | End CRON  ==========', date('Y-m-d H:i:s')) . PHP_EOL;
	}

	/**
	 * Clear laststart/lastend and unlock one task or every registered task.
	 *
	 * @param string|null $serviceName Task name (LBL_*), or null to reset all tasks
	 * @return void
	 */
	public function resetSchedule(?string $serviceName = null): void
	{
		\vtlib\Cron::setCronAction(true);

		if (PHP_SAPI !== 'cli') {
			echo '<pre>';
		}

		if ($serviceName !== null) {
			$cronTask = \vtlib\Cron::getInstance($serviceName);
			if ($cronTask === null) {
				echo sprintf('ERROR: Cron task "%s" not found' . PHP_EOL, $serviceName);
				return;
			}
			$cronTask->resetSchedule();
			echo sprintf('%s | %s - Schedule reset (laststart/lastend cleared, status enabled)' . PHP_EOL, date('Y-m-d H:i:s'), $cronTask->getName());
			return;
		}

		$rows = (new \App\Db\Query())
			->select(['name'])
			->from('vtiger_cron_task')
			->orderBy(['sequence' => SORT_ASC])
			->all();
		foreach ($rows as $row) {
			$cronTask = \vtlib\Cron::getInstance($row['name']);
			if ($cronTask === null) {
				continue;
			}
			$cronTask->resetSchedule();
			echo sprintf('%s | %s - Schedule reset (laststart/lastend cleared, status enabled)' . PHP_EOL, date('Y-m-d H:i:s'), $cronTask->getName());
		}
	}

	/**
	 * Run a single cron task
	 * @param \vtlib\Cron $cronTask
	 * @param bool $ignoreFrequency When true (explicit `service=` from CLI/web), skip the per-task frequency wait so the job runs immediately.
	 * @return void
	 */
	public function runTask(\vtlib\Cron $cronTask, bool $ignoreFrequency = false): void
	{
		$taskName = $cronTask->getName();
		$taskMarkedRunning = false;
		$startTime = null;

		\App\Log\Log::trace($taskName . ' - Start');

		// Timeout could happen if intermediate cron-tasks fails
		// and affect the next task. Which need to be handled in this cycle.
		if ($cronTask->hadTimeout()) {
			echo sprintf('%s | %s - Cron task had timedout as it was not completed last time it run' . PHP_EOL, date('Y-m-d H:i:s'), $taskName);
			if (\App\Core\AppConfig::main('unblockedTimeoutCronTasks')) {
				$cronTask->unlockTask();
			}
		}

		// Not ready to run yet?
		if ($cronTask->isRunning()) {
			\App\Log\Log::trace($taskName . ' - Task omitted, it has not been finished during the last scanning');
			echo sprintf('%s | %s - Task omitted, it has not been finished during the last scanning' . PHP_EOL, date('Y-m-d H:i:s'), $taskName);
			return;
		}

		// Not ready to run yet? (skipped when a single task was requested via service=…)
		if (!$ignoreFrequency && !$cronTask->isRunnable()) {
			\App\Log\Log::trace($taskName . ' - Not ready to run as the time to run again is not completed');
			echo sprintf('%s | %s - Not ready to run as the time to run again is not completed' . PHP_EOL, date('Y-m-d H:i:s'), $taskName);
			return;
		}

		try {
			$cronTask->markRunning();
			$taskMarkedRunning = true;

			echo sprintf('%s | %s - Start task' . PHP_EOL, date('Y-m-d H:i:s'), $taskName);
			$startTime = microtime(true);

			$handlerClass = trim((string) $cronTask->getHandlerClass());
			if ($handlerClass === '') {
				throw new \RuntimeException('Cron task has no handler_class.');
			}
			$this->runClassHandler($cronTask, $handlerClass);
		} catch (Throwable $e) {
			\App\Log\Log::error($e, 'CRON');
			echo sprintf('%s | ERROR: %s - Cron task execution threw exception.' . PHP_EOL, date('Y-m-d H:i:s'), $taskName);
			echo $e->getMessage() . PHP_EOL;
			if (\App\Core\AppConfig::main('systemMode') === 'test') {
				throw $e;
			}
		} finally {
			if ($taskMarkedRunning) {
				$cronTask->markFinished();
			}
			$taskTime = $startTime ? round(microtime(true) - $startTime, 2) : 0;
			echo sprintf('%s | %s - End task (%s s)', date('Y-m-d H:i:s'), $taskName, $taskTime) . PHP_EOL;
			\App\Log\Log::trace($taskName . ' - End');
		}
	}

	/**
	 * Execute class-based handler.
	 *
	 * @param \vtlib\Cron $cronTask
	 * @param string $handlerClass
	 *
	 * @throws \RuntimeException
	 */
	private function runClassHandler(\vtlib\Cron $cronTask, string $handlerClass): void
	{
		$taskName = $cronTask->getName();
		if (!class_exists($handlerClass)) {
			throw new \RuntimeException(sprintf('Cron task handler_class "%s" does not exist.', $handlerClass));
		}

		$instance = new $handlerClass();
		if (!$instance instanceof CronTaskInterface) {
			throw new \RuntimeException(sprintf('Cron task handler_class "%s" must implement %s.', $handlerClass, CronTaskInterface::class));
		}

		$params = $this->decodeHandlerParams($cronTask->getHandlerParams());
		if (!empty($params) && method_exists($instance, 'setParams')) {
			call_user_func([$instance, 'setParams'], $params);
		}

		$instance->execute();
	}

	/**
	 * @param mixed $raw
	 * @return array
	 */
	private function decodeHandlerParams($raw): array
	{
		if (empty($raw)) {
			return [];
		}
		if (is_array($raw)) {
			return $raw;
		}
		if (is_string($raw)) {
			$decoded = json_decode($raw, true);
			return is_array($decoded) ? $decoded : [];
		}
		return [];
	}
}

