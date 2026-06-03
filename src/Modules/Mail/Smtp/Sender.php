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

namespace App\Modules\Mail\Smtp;

use App\Modules\Mail\Models\Account;
use PHPMailer\PHPMailer\PHPMailer;

class Sender
{
	/**
	 * @param array<string, mixed> $account
	 * @param array{to: array, cc?: array, bcc?: array, subject: string, body_html: string, body_text?: string, attachments?: array<string, string>} $envelope
	 */
	public static function send(array $account, array $envelope): array
	{
		$password = Account::getDecryptedPassword($account);
		try {
			$mailer = new PHPMailer(true);
			$mailer->isSMTP();
			$mailer->Host = $account['smtp_host'];
			$mailer->Port = (int) ($account['smtp_port'] ?? 465);
			$secure = $account['smtp_secure'] ?? 'ssl';
			if ($secure === 'ssl') {
				$mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			} elseif ($secure === 'tls') {
				$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			}
			$mailer->SMTPAuth = true;
			$mailer->Username = $account['username'];
			$mailer->Password = $password;
			$mailer->CharSet = \App\Core\AppConfig::main('default_charset');

			$fromEmail = (string) $account['username'];
			$fromName = (string) ($account['from_name'] ?? '');
			$mailer->setFrom($fromEmail, $fromName);
			self::applyReplyTo($mailer, $account);

			foreach ($envelope['to'] as $addr) {
				$mailer->addAddress($addr);
			}
			foreach ($envelope['cc'] ?? [] as $addr) {
				$mailer->addCC($addr);
			}
			foreach ($envelope['bcc'] ?? [] as $addr) {
				$mailer->addBCC($addr);
			}

			$mailer->Subject = $envelope['subject'];
			$mailer->msgHTML($envelope['body_html']);
			if (isset($envelope['body_text']) && $envelope['body_text'] !== '') {
				$mailer->AltBody = $envelope['body_text'];
			}

			foreach ($envelope['attachments'] ?? [] as $path => $name) {
				$mailer->addAttachment($path, $name);
			}

			$mailer->send();
			$messageId = $mailer->getLastMessageID() ?: self::generateMessageId($fromEmail);
			$rfc822 = $mailer->getSentMIMEMessage();

			return ['success' => true, 'message_id' => $messageId, 'rfc822' => $rfc822];
		} catch (\Throwable $e) {
			return ['success' => false, 'error' => $e->getMessage()];
		} finally {
			$password = '';
		}
	}

	private static function applyReplyTo(PHPMailer $mailer, array $account): void
	{
		$mode = $account['reply_to_mode'] ?? 'same_as_from';
		if ($mode === 'custom' && !empty($account['reply_to_address'])) {
			$mailer->addReplyTo((string) $account['reply_to_address']);
			return;
		}
		if ($mode === 'user_personal') {
			$userId = (int) (\App\User\CurrentUser::getId() ?? 0);
			$user = $userId ? \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users') : null;
			if ($user && $user->get('email1')) {
				$mailer->addReplyTo((string) $user->get('email1'), (string) $user->getName());
				return;
			}
		}
		$mailer->addReplyTo((string) $account['username'], (string) ($account['from_name'] ?? ''));
	}

	private static function generateMessageId(string $fromEmail): string
	{
		$domain = substr(strrchr($fromEmail, '@') ?: '@local', 1) ?: 'local';
		return '<' . bin2hex(random_bytes(16)) . '@' . $domain . '>';
	}
}
