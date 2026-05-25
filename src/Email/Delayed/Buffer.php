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

namespace App\Email\Delayed;

final class Buffer
{
	public static function enqueue(
		int $sourceId,
		int $destId,
		DelayedEmailType $type,
		Email $email,
		?int $delayMinutes = null
	): void {
		if (!\App\Core\AppConfig::module('Mail', 'DELAYED_EMAIL_BUFFER_ENABLED')) {
			self::enqueueImmediate($email);
			return;
		}

		$delay = $delayMinutes
			?? (int) (\App\Core\AppConfig::module('Mail', 'DELAYED_EMAIL_DEFAULT_MINUTES') ?: 120);

		$hash = $type->resolver()?->hash($sourceId, $destId);

		$recipients = $email->recipients;
		if (!isset($recipients['to'])) {
			$recipients['to'] = [];
		}

		\App\Db\Db::getInstance('admin')->createCommand(
			'INSERT INTO s_#__delayed_email_queue
				 (source_id, dest_id, type, recipients_json, subject, body,
				  expected_state_hash, send_after, created_at)
			 VALUES
				 (:src, :dst, :type, :rcpts, :subj, :body,
				  :hash, NOW() + INTERVAL :delay MINUTE, NOW())
			 ON DUPLICATE KEY UPDATE
				 recipients_json     = VALUES(recipients_json),
				 subject             = VALUES(subject),
				 body                = VALUES(body),
				 expected_state_hash = VALUES(expected_state_hash),
				 send_after          = VALUES(send_after),
				 created_at          = VALUES(created_at)',
			[
				':src' => $sourceId,
				':dst' => $destId,
				':type' => $type->value,
				':rcpts' => \App\Utils\Json::encode($recipients),
				':subj' => $email->subject,
				':body' => $email->body,
				':hash' => $hash,
				':delay' => $delay,
			]
		)->execute();
	}

	/**
	 * @param array{smtp_id?: int, to?: array, cc?: array, bcc?: array, subject?: string, content?: string, from?: array} $mailerContent
	 */
	public static function enqueueFromMailerContent(
		int $sourceId,
		int $destId,
		DelayedEmailType $type,
		array $mailerContent,
		?int $delayMinutes = null
	): void {
		$recipients = [
			'to' => $mailerContent['to'] ?? [],
			'cc' => $mailerContent['cc'] ?? [],
			'bcc' => $mailerContent['bcc'] ?? [],
		];
		$email = new Email(
			$recipients,
			(string) ($mailerContent['subject'] ?? ''),
			(string) ($mailerContent['content'] ?? ''),
		);
		if ($email->body === '') {
			return;
		}
		if (!\App\Core\AppConfig::module('Mail', 'DELAYED_EMAIL_BUFFER_ENABLED')) {
			\App\Email\Mailer::addMail($mailerContent);
			return;
		}
		self::enqueue($sourceId, $destId, $type, $email, $delayMinutes);
	}

	public static function cancel(int $sourceId, int $destId, ?DelayedEmailType $type = null): int
	{
		$conditions = ['source_id' => $sourceId, 'dest_id' => $destId];
		if ($type !== null) {
			$conditions['type'] = $type->value;
		}
		return (int) \App\Db\Db::getInstance('admin')
			->createCommand()
			->delete('s_#__delayed_email_queue', $conditions)
			->execute();
	}

	public static function cancelById(int $id): int
	{
		return (int) \App\Db\Db::getInstance('admin')
			->createCommand()
			->delete('s_#__delayed_email_queue', ['id' => $id])
			->execute();
	}

	public static function sendNow(int $bufferId): void
	{
		\App\Db\Db::getInstance('admin')->createCommand()->update(
			's_#__delayed_email_queue',
			['send_after' => new \yii\db\Expression('NOW()')],
			['id' => $bufferId]
		)->execute();
	}

	private static function enqueueImmediate(Email $email): void
	{
		$params = [
			'to' => $email->recipients['to'] ?? [],
			'subject' => $email->subject,
			'content' => $email->body,
		];
		if (!empty($email->recipients['cc'])) {
			$params['cc'] = $email->recipients['cc'];
		}
		if (!empty($email->recipients['bcc'])) {
			$params['bcc'] = $email->recipients['bcc'];
		}
		\App\Email\Mailer::addMail($params);
	}
}
