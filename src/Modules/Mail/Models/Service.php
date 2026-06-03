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

		$query = (new \App\Db\Query())
			->select(['m.*'])
			->from(['m' => 'u_yf_mail_messages'])
			->innerJoin(['l' => 'u_yf_mail_record_links'], 'l.message_id = m.id')
			->leftJoin(['a' => 'u_yf_mail_accounts'], 'a.id = m.account_id')
			->where(['l.crm_module' => $module, 'l.crm_record_id' => $recordId])
			->orderBy(['m.date_sent' => SORT_DESC])
			->limit($limit);

		if (!$isAdmin) {
			$query->andWhere([
				'or',
				['m.smtp_id' => null, 'a.kind' => 'shared'],
				['not', ['m.smtp_id' => null]],
				['and', ['a.kind' => 'personal'], ['a.owner_user_id' => $userId]],
				['and', ['m.direction' => 'out'], ['m.sender_user_id' => $userId]],
			]);
		}

		return $query->all();
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
