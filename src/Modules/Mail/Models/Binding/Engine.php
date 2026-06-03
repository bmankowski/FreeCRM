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

namespace App\Modules\Mail\Models\Binding;

class Engine
{
	public static function bindMessage(array $messageRow, array $parsedAddresses, bool $skipEmailLookup = false): array
	{
		$messageId = (int) $messageRow['id'];
		$linked = $skipEmailLookup ? [] : ByEmail::bind($messageId, $parsedAddresses);
		$subjectLink = HelpDeskSubject::bind($messageId, (string) ($messageRow['subject'] ?? ''));
		if ($subjectLink) {
			$linked[] = $subjectLink;
		}
		return $linked;
	}

	public static function link(int $messageId, string $module, int $recordId, string $linkType, ?string $matchField): bool
	{
		$exists = (new \App\Db\Query())
			->from('u_yf_mail_record_links')
			->where(['message_id' => $messageId, 'crm_module' => $module, 'crm_record_id' => $recordId])
			->exists();
		if ($exists) {
			return false;
		}
		\App\Db\Db::getInstance()->createCommand()->insert('u_yf_mail_record_links', [
			'message_id' => $messageId,
			'crm_module' => $module,
			'crm_record_id' => $recordId,
			'link_type' => $linkType,
			'match_field' => $matchField,
		])->execute();
		return true;
	}

	public static function unlink(int $linkId): bool
	{
		return (bool) \App\Db\Db::getInstance()->createCommand()->delete('u_yf_mail_record_links', ['id' => $linkId])->execute();
	}
}
