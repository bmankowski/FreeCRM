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
		if (Service::getUserAccounts($userId, true) !== []) {
			return true;
		}
		return (bool) \App\Email\Mail::getDefaultSmtp();
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
			return Service::getUserAccounts($userId, true) !== [];
		}
		return self::canUserSend($userId);
	}

	public static function defaultSenderRefForTemplate(array $template, int $userId): string
	{
		$type = self::resolveSenderType($template);
		if ($type === 'user_account') {
			$default = Service::getDefaultAccount($userId);
			if ($default) {
				return 'account:' . (int) $default['id'];
			}
			$accounts = Service::getUserAccounts($userId, true);
			return $accounts !== [] ? 'account:' . (int) $accounts[0]['id'] : '';
		}
		if ($type === 'system_smtp') {
			$smtpId = \App\Email\Mail::resolveTemplateSmtpId($template) ?: \App\Email\Mail::getDefaultSmtp();

			return 'smtp:' . (int) $smtpId;
		}
		$smtpId = \App\Email\Mail::resolveTemplateSmtpId($template);
		if ($smtpId) {
			return 'smtp:' . (int) $smtpId;
		}
		$default = Service::getDefaultAccount($userId);
		if ($default) {
			return 'account:' . (int) $default['id'];
		}
		$accounts = Service::getUserAccounts($userId, true);
		if ($accounts !== []) {
			return 'account:' . (int) $accounts[0]['id'];
		}

		return 'smtp:' . (int) \App\Email\Mail::getDefaultSmtp();
	}

	/**
	 * @param array<string, mixed> $mailParams
	 */
	public static function dispatchOutbound(int $userId, string $senderRef, array $mailParams, ?array $templateDetail = null): void
	{
		if (str_starts_with($senderRef, 'account:')) {
			\App\Modules\Mail\Models\Outbound::sendViaAccount($userId, (int) substr($senderRef, 8), $mailParams);

			return;
		}
		if (str_starts_with($senderRef, 'smtp:')) {
			$smtpId = (int) substr($senderRef, 5);
			$queueParams = [
				'smtp_id' => $smtpId,
				'subject' => $mailParams['subject'] ?? '',
				'content' => $mailParams['content'] ?? $mailParams['body_html'] ?? '',
				'to' => $mailParams['to'] ?? [],
				'source_module' => $mailParams['sourceModule'] ?? $mailParams['source_module'] ?? null,
				'source_id' => $mailParams['sourceRecord'] ?? $mailParams['source_id'] ?? null,
			];
			if ($templateDetail !== null && isset($templateDetail['attachments'])) {
				$queueParams['attachments'] = $templateDetail['attachments'];
			}
			\App\Email\Mailer::addMail(array_intersect_key($queueParams, array_flip(\App\Email\Mailer::$quoteColumn)));
			\App\Modules\Mail\Models\Outbound::recordSystemSend($userId, $smtpId, $mailParams);

			return;
		}
		throw new \App\Exceptions\AppException('Invalid sender');
	}
}
