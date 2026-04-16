<?php

namespace App\Modules\Cron\Tasks;

use App\Modules\Cron\Contract\CronTaskInterface;

/**
 * Abstract base class for all cron tasks
 * @package App\Modules\Cron\Tasks
 */
abstract class AbstractCronTask implements CronTaskInterface
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
		\App\Log\Log::$level($message);
	}

	/**
	 * Get database instance
	 * @return \yii\db\Connection
	 */
	protected function getDb(): \yii\db\Connection
	{
		return \App\Db\Db::getInstance();
	}

	/**
	 * Get admin database instance
	 * @return \yii\db\Connection
	 */
	protected function getAdminDb(): \yii\db\Connection
	{
		return \App\Db\Db::getInstance('admin');
	}
}

