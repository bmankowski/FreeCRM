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

final class MailerTask extends AbstractCronTask
{
	public function execute(): void
	{
		$db = \App\Db\Db::getInstance('admin');
		$rows = (new \App\Db\Query())->from('s_#__mail_queue')
			->where(['status' => 1])
			->orderBy(['priority' => SORT_DESC, 'date' => SORT_ASC])
			->limit(\App\Core\AppConfig::performance('CRON_MAX_NUMBERS_SENDING_MAILS'))
			->all($db);

		if ($rows === []) {
			return;
		}

		$auditEnabled = (bool) \App\Core\AppConfig::module('Mail', 'MAIL_AUDIT_LOG_ENABLED');

		$rowsBySmtp = [];
		foreach ($rows as $rowQueue) {
			$rowsBySmtp[(int) $rowQueue['smtp_id']][] = $rowQueue;
		}

		foreach ($rowsBySmtp as $smtpId => $smtpRows) {
			$sessionMailer = null;
			try {
				$sessionMailer = \App\Email\Mailer::createQueueSessionMailer($smtpId);
				foreach ($smtpRows as $rowQueue) {
					$errorMsg = null;
					$status = false;
					try {
						$status = \App\Email\Mailer::sendByRowQueue($rowQueue, $sessionMailer);
					} catch (\Throwable $e) {
						$errorMsg = $e->getMessage();
						\App\Log\Log::warning('MailerTask send failed id=' . $rowQueue['id'] . ': ' . $errorMsg, 'Mailer');
					}

					if ($auditEnabled) {
						$this->finalizeQueueRow($db, $rowQueue, $status, $errorMsg);
					} elseif ($status) {
						$db->createCommand()->delete('s_#__mail_queue', ['id' => $rowQueue['id']])->execute();
					} else {
						$db->createCommand()->update('s_#__mail_queue', ['status' => 2], ['id' => $rowQueue['id']])->execute();
					}
				}
			} finally {
				$sessionMailer?->closeSmtpSession();
			}
		}
	}

	private function finalizeQueueRow(\yii\db\Connection $db, array $rowQueue, bool $status, ?string $errorMsg): void
	{
		$db->transaction(function () use ($db, $rowQueue, $status, $errorMsg) {
			$db->createCommand()->insert('s_#__mail_sent_log', [
				'mail_queue_id' => (int) $rowQueue['id'],
				'smtp_id' => (int) $rowQueue['smtp_id'],
				'owner' => $rowQueue['owner'] ?? null,
				'recipients_json' => \App\Utils\Json::encode([
					'to' => $rowQueue['to'] ?? null,
					'cc' => $rowQueue['cc'] ?? null,
					'bcc' => $rowQueue['bcc'] ?? null,
				]),
				'subject' => mb_substr((string) ($rowQueue['subject'] ?? ''), 0, 998),
				'body_sha256' => hash('sha256', (string) ($rowQueue['content'] ?? '')),
				'body_excerpt' => mb_substr(strip_tags((string) ($rowQueue['content'] ?? '')), 0, 500),
				'status' => $status ? 1 : 2,
				'error' => $status ? null : $errorMsg,
				'source_module' => $rowQueue['source_module'] ?? null,
				'source_id' => isset($rowQueue['source_id']) ? (int) $rowQueue['source_id'] : null,
			])->execute();

			if ($status) {
				$db->createCommand()->delete('s_#__mail_queue', ['id' => $rowQueue['id']])->execute();
			} else {
				$db->createCommand()->update('s_#__mail_queue', ['status' => 2], ['id' => $rowQueue['id']])->execute();
			}
		});
	}
}
