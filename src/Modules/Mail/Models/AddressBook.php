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

namespace App\Modules\Mail\Models;

class AddressBook
{
	public const TABLE = 'u_yf_mail_address_book';

	public static function createABFile(): void
	{
		$mails = [];
		$rows = (new \App\Db\Query())->from(self::TABLE)->all();
		foreach ($rows as $row) {
			$name = $row['name'];
			$email = $row['email'];
			$users = $row['users'];
			if ($users === '' || $users === null) {
				continue;
			}
			foreach (explode(',', ltrim((string) $users, ',')) as $user) {
				if ($user === '') {
					continue;
				}
				$mails[$user] = ($mails[$user] ?? '') . "'" . addslashes((string) $name) . " <$email>',";
			}
		}
		$fstart = '<?php $bookMails = [';
		$fend = '];';
		foreach ($mails as $user => $file) {
			$dir = ROOT_DIRECTORY . '/cache/addressBook';
			if (!is_dir($dir)) {
				mkdir($dir, 0755, true);
			}
			file_put_contents($dir . '/mails_' . $user . '.php', $fstart . $file . $fend);
		}
	}
}
