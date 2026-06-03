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
use App\Modules\Mail\Imap\Fetcher;
use App\Modules\Mail\Models\Account;
use App\Modules\Mail\Models\MailLog;

final class Scanner extends AbstractCronTask
{
	public function execute(): void
	{
		foreach (Account::getAccountsDueForScan() as $account) {
			try {
				$count = Fetcher::fetchAccount($account);
				Account::markScanResult((int) $account['id'], true, (int) ($account['last_uid'] ?? 0));
				MailLog::write('scan', "Scanned account {$account['id']}, new messages: $count", 'info', (int) $account['id']);
			} catch (\Throwable $e) {
				Account::markScanResult((int) $account['id'], false, 0, $e->getMessage());
				MailLog::write('scan', $e->getMessage(), 'error', (int) $account['id']);
			}
		}
	}
}
