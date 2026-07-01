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

use App\Email\Delayed\DelayedEmailType;

final class DelayedEmailQueueTask extends AbstractCronTask
{
	public function execute(): void
	{
		$dueIds = (new \App\Db\Query())
			->select(['id'])
			->from('s_#__delayed_email_queue')
			->where(['<=', 'send_after', new \yii\db\Expression('NOW()')])
			->orderBy(['send_after' => SORT_ASC])
			->limit(50)
			->column();

		$promoted = 0;
		$stale = 0;
		foreach ($dueIds as $id) {
			$result = $this->promoteOne((int) $id);
			if ($result === 'promoted') {
				++$promoted;
			} elseif ($result === 'stale') {
				++$stale;
			}
		}
		\App\Log\Log::trace(
			'DelayedEmailQueueTask finished promoted=' . $promoted . ' stale=' . $stale . ' scanned=' . count($dueIds),
			'Cron'
		);
	}

	/**
	 * @return 'promoted'|'stale'|'skipped'
	 */
	private function promoteOne(int $id): string
	{
		$db = \App\Db\Db::getInstance('admin');
		$result = 'skipped';

		$db->transaction(function () use ($db, $id, &$result) {
			$row = $db->createCommand(
				'SELECT * FROM s_#__delayed_email_queue WHERE id = :id FOR UPDATE',
				[':id' => $id]
			)->queryOne();

			if (!$row) {
				return;
			}

			if (!$this->isStillRelevant($row)) {
				$db->createCommand()->delete('s_#__delayed_email_queue', ['id' => $id])->execute();
				\App\Log\Log::trace('DelayedEmailQueueTask stale id=' . $id, 'Cron');
				$result = 'stale';
				return;
			}

			$recipients = \App\Utils\Json::decode($row['recipients_json']);
			$senderRef = (string) ($recipients['_sender_ref'] ?? '');
			unset($recipients['_sender_ref']);
			if ($senderRef === '') {
				throw new \App\Exceptions\AppException('LBL_MAIL_SENDER_REF_REQUIRED');
			}
			$mailParams = [
				'to' => $recipients['to'] ?? [],
				'subject' => $row['subject'],
				'content' => $row['body'],
				'source_module' => 'DelayedBuffer',
				'source_id' => (int) $row['id'],
				'params' => ['sender_ref' => $senderRef],
			];
			if (!empty($recipients['cc'])) {
				$mailParams['cc'] = $recipients['cc'];
			}
			if (!empty($recipients['bcc'])) {
				$mailParams['bcc'] = $recipients['bcc'];
			}

			\App\Email\Mailer::addMail($mailParams);
			$db->createCommand()->delete('s_#__delayed_email_queue', ['id' => $id])->execute();
			\App\Log\Log::trace('DelayedEmailQueueTask promoted id=' . $id, 'Cron');
			$result = 'promoted';
		});

		return $result;
	}

	private function isStillRelevant(array $row): bool
	{
		if ($row['expected_state_hash'] === null || $row['expected_state_hash'] === '') {
			return true;
		}
		$type = DelayedEmailType::tryFrom((string) $row['type']);
		if ($type === null) {
			return true;
		}
		$resolver = $type->resolver();
		if ($resolver === null) {
			return true;
		}
		return hash_equals(
			(string) $row['expected_state_hash'],
			$resolver->hash((int) $row['source_id'], (int) $row['dest_id'])
		);
	}
}
