<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Mail\Cron;

use App\Modules\Cron\Tasks\AbstractCronTask;

final class LogPrune extends AbstractCronTask
{
	public function execute(): void
	{
		$infoDays = (int) (\App\Core\AppConfig::module('Mail', 'purge_info_logs_days') ?? 30);
		$errorDays = (int) (\App\Core\AppConfig::module('Mail', 'purge_error_logs_days') ?? 180);
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->delete('u_yf_mail_log', [
			'and',
			['level' => 'info'],
			['<', 'created_at', date('Y-m-d H:i:s', strtotime("-{$infoDays} days"))],
		])->execute();
		$db->createCommand()->delete('u_yf_mail_log', [
			'and',
			['in', 'level', ['warn', 'error']],
			['<', 'created_at', date('Y-m-d H:i:s', strtotime("-{$errorDays} days"))],
		])->execute();
	}
}
