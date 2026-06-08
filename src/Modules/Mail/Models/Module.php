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

class Module extends \App\Modules\Base\Models\Module
{
	public static function getMessageDetailUrl(int $messageId, ?string $sourceModule = null, ?int $sourceRecord = null): string
	{
		$params = [
			'module' => 'Mail',
			'view' => 'Detail',
			'record' => $messageId,
		];
		if ($sourceModule !== null && $sourceModule !== '' && $sourceRecord !== null && $sourceRecord > 0) {
			$params['sourceModule'] = $sourceModule;
			$params['sourceRecord'] = $sourceRecord;
		}

		return 'index.php?' . http_build_query($params);
	}

	public static function getParentRelatedListUrl(string $parentModule, int $parentRecordId): string
	{
		return 'index.php?' . http_build_query([
			'module' => $parentModule,
			'view' => 'Detail',
			'record' => $parentRecordId,
			'relatedModule' => 'Mail',
			'mode' => 'showRelatedList',
			'tab_label' => 'LBL_MAILS',
		]);
	}

	public static function getComposeUrl(
		string $sourceModule,
		int $sourceRecord,
		?string $to = null,
		?string $recordModule = null,
		?int $recordId = null
	): string {
		$params = [
			'module' => 'Mail',
			'view' => 'Compose',
			'sourceModule' => $sourceModule,
			'sourceRecord' => $sourceRecord,
		];
		if ($to !== null && $to !== '') {
			$params['to'] = $to;
		}
		if ($recordModule !== null && $recordId !== null
			&& ($recordModule !== $sourceModule || $recordId !== $sourceRecord)) {
			$params['recordModule'] = $recordModule;
			$params['recordId'] = $recordId;
		}
		return 'index.php?' . http_build_query($params);
	}

	public static function canUserSend(int $userId): bool
	{
		if (!\App\Core\AppConfig::main('isActiveSendingMails')) {
			return false;
		}

		return Account::getComposeSenders($userId) !== [];
	}

	public static function resolveSenderType(array $template): string
	{
		return (string) ($template['sender_type'] ?? 'system_smtp');
	}

	public static function userCanSendTemplate(int $userId, array $template): bool
	{
		$type = self::resolveSenderType($template);
		if ($type === 'system_smtp') {
			return (bool) (\App\Email\Mail::resolveTemplateSmtpId($template) ?: \App\Email\Mail::getDefaultSmtp());
		}
		if ($type === 'user_account') {
			return Account::getComposeSenders($userId) !== [];
		}
		return self::canUserSend($userId);
	}

	public static function defaultSenderRefForTemplate(array $template, int $userId): string
	{
		$type = self::resolveSenderType($template);
		if ($type === 'user_account') {
			return self::defaultUserAccountRef($userId);
		}
		if ($type === 'system_smtp') {
			$smtpId = \App\Email\Mail::resolveTemplateSmtpId($template) ?: \App\Email\Mail::getDefaultSmtp();

			return 'smtp:' . (int) $smtpId;
		}
		$accountRef = self::defaultUserAccountRef($userId);
		if ($accountRef !== '') {
			return $accountRef;
		}
		$smtpId = \App\Email\Mail::resolveTemplateSmtpId($template);
		if ($smtpId) {
			return 'smtp:' . (int) $smtpId;
		}

		return 'smtp:' . (int) \App\Email\Mail::getDefaultSmtp();
	}

	private static function defaultUserAccountRef(int $userId): string
	{
		$senders = Account::getComposeSenders($userId);
		if ($senders === []) {
			return '';
		}

		return $senders[0]['ref'];
	}
}
