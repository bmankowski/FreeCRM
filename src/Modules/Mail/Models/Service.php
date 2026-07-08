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

class Service
{
	public static function getMessagesForRecord(string $module, int $recordId, int $userId, int $limit = 50): array
	{
		/** @var \App\Modules\Users\Models\Record $user */
		$user = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
		$isAdmin = $user->isAdminUser();

		$openSubquery = (new \App\Db\Query())
			->select(['mail_message_id', 'opened_at' => 'MIN(clicked_at)'])
			->from('u_yf_link_action_log')
			->where(['action' => 'open'])
			->andWhere(['not', ['mail_message_id' => null]])
			->groupBy('mail_message_id');

		$query = (new \App\Db\Query())
			->select(['m.*', 'open.opened_at'])
			->from(['m' => 'u_yf_mail_messages'])
			->innerJoin(['l' => 'u_yf_mail_record_links'], 'l.message_id = m.id')
			->leftJoin(['open' => $openSubquery], 'open.mail_message_id = m.id')
			->leftJoin(['a' => 'u_yf_mail_accounts'], 'a.id = m.account_id')
			->where(['l.crm_module' => $module, 'l.crm_record_id' => $recordId])
			->orderBy(['m.date_sent' => SORT_DESC])
			->limit($limit);

		if (!$isAdmin) {
			$query->andWhere([
				'or',
				['m.smtp_id' => null, 'a.kind' => 'group'],
				['not', ['m.smtp_id' => null]],
				['and', ['a.kind' => 'personal'], ['a.owner_user_id' => $userId]],
				['and', ['m.direction' => 'out'], ['m.sender_user_id' => $userId]],
			]);
		}

		$rows = $query->all();

		return array_map(static fn (array $row): array => self::formatMessageListRow($row), $rows);
	}

	/**
	 * @return array{prev: ?int, next: ?int} Adjacent message IDs in the same order as getMessagesForRecord (date_sent DESC).
	 */
	public static function getAdjacentMessageIds(string $module, int $recordId, int $currentId, int $userId): array
	{
		$messages = self::getMessagesForRecord($module, $recordId, $userId, 500);
		$ids = array_map(static fn (array $row): int => (int) $row['id'], $messages);
		$idx = array_search($currentId, $ids, true);
		if ($idx === false) {
			return ['prev' => null, 'next' => null];
		}

		return [
			'prev' => $idx > 0 ? $ids[$idx - 1] : null,
			'next' => $idx < \count($ids) - 1 ? $ids[$idx + 1] : null,
		];
	}

	public static function getFirstOpenedAt(int $messageId): ?string
	{
		if ($messageId <= 0) {
			return null;
		}
		$value = (new \App\Db\Query())
			->from('u_yf_link_action_log')
			->where(['action' => 'open', 'mail_message_id' => $messageId])
			->min('clicked_at');

		return is_string($value) && $value !== '' ? $value : null;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	public static function formatMessageListRow(array $row): array
	{
		if (($row['direction'] ?? '') === 'out' && !empty($row['opened_at'])) {
			$row['opened_at_display'] = \App\Modules\Base\UiTypes\Datetime::getDateTimeValue((string) $row['opened_at']);
		} else {
			$row['opened_at_display'] = '';
		}

		return $row;
	}

	public static function getUserAccounts(int $userId): array
	{
		return Account::getUserAccounts($userId, true);
	}

	public static function getDefaultAccount(int $userId): ?array
	{
		$id = Account::getDefaultAccountId($userId);
		return $id ? Account::getById($id) : null;
	}
}
