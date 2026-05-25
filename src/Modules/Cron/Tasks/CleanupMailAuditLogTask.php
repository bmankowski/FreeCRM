<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Cron\Tasks;

final class CleanupMailAuditLogTask extends AbstractCronTask
{
	public function execute(): void
	{
		$days = (int) (\App\Core\AppConfig::module('Mail', 'AUDIT_LOG_RETENTION_DAYS') ?: 365);
		if ($days < 1) {
			return;
		}
		$deleted = \App\Db\Db::getInstance('admin')->createCommand(
			'DELETE FROM s_#__mail_sent_log WHERE attempted_at < NOW() - INTERVAL :days DAY',
			[':days' => $days]
		)->execute();
		\App\Log\Log::trace('CleanupMailAuditLogTask deleted=' . $deleted . ' older_than_days=' . $days, 'Cron');
	}
}
