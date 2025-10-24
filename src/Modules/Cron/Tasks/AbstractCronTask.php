<?php

namespace App\Modules\Cron\Tasks;

/**
 * Abstract base class for all cron tasks
 * @package App\Modules\Cron\Tasks
 */
abstract class AbstractCronTask
{
	/**
	 * Execute the cron task
	 * @return void
	 */
	abstract public function execute(): void;

	/**
	 * Log a message
	 * @param string $message
	 * @param string $level
	 * @return void
	 */
	protected function log(string $message, string $level = 'trace'): void
	{
		\App\Log::$level($message);
	}

	/**
	 * Get database instance
	 * @return \App\Db
	 */
	protected function getDb(): \App\Db
	{
		return \App\Db::getInstance();
	}

	/**
	 * Get admin database instance
	 * @return \App\Db
	 */
	protected function getAdminDb(): \App\Db
	{
		return \App\Db::getInstance('admin');
	}
}

