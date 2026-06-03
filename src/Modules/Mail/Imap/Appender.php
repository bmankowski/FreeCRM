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

namespace App\Modules\Mail\Imap;

use App\Modules\Mail\Models\Account;

class Appender
{
	public static function appendToSent(array $account, string $rfc822): bool
	{
		$folder = $account['imap_folder_sent'] ?? null;
		if (empty($folder) || empty($account['append_sent'])) {
			return false;
		}
		try {
			$client = Client::createFromAccount($account);
			$client->connect();
			$client->getFolder($folder)->appendMessage($rfc822, ['\\Seen']);
			$client->disconnect();
			return true;
		} catch (\Throwable $e) {
			\App\Modules\Mail\Models\MailLog::write('append_sent', $e->getMessage(), 'warn', (int) ($account['id'] ?? 0));
			return false;
		}
	}
}
