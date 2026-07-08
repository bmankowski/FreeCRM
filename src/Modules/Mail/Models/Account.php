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

class Account
{
	private const PERSONAL_DEFAULT_FIELDS = [
		'name',
		'imap_host',
		'imap_port',
		'imap_secure',
		'imap_validate_cert',
		'imap_folder_inbox',
		'imap_folder_sent',
		'smtp_host',
		'smtp_port',
		'smtp_secure',
		'username',
		'from_name',
		'reply_to_mode',
		'reply_to_address',
		'append_sent',
	];

	public static function passwordMask(): string
	{
		return (string) (\App\Core\AppConfig::module('Mail', 'password_mask') ?? '**********');
	}

	public static function getById(int $id): ?array
	{
		$row = (new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $id])->one();
		return $row ? self::sanitizeForDisplay($row) : null;
	}

	public static function getPersonalAccountDefaults(): array
	{
		$defaults = \App\Core\AppConfig::module('Mail', 'personal_account_defaults');
		return is_array($defaults) ? $defaults : [];
	}

	public static function applyPersonalDefaults(array $data, int $userId): array
	{
		$defaults = self::getPersonalAccountDefaults();
		$hints = self::getUserPersonalHints($userId);
		$result = $data;

		foreach (self::PERSONAL_DEFAULT_FIELDS as $field) {
			if (!self::isEmptyAccountField($result[$field] ?? null)) {
				continue;
			}
			if (!self::isEmptyAccountField($hints[$field] ?? null)) {
				$result[$field] = $hints[$field];
				continue;
			}
			if (array_key_exists($field, $defaults) && !self::isEmptyAccountField($defaults[$field])) {
				$result[$field] = $defaults[$field];
			}
		}

		if (self::isEmptyAccountField($result['name'] ?? null) && !self::isEmptyAccountField($result['username'] ?? null)) {
			$result['name'] = $result['username'];
		}

		return $result;
	}

	public static function getPersonalForDisplay(int $userId): array
	{
		$row = (new \App\Db\Query())
			->from('u_yf_mail_accounts')
			->where(['kind' => 'personal', 'owner_user_id' => $userId])
			->one();

		return self::sanitizeForDisplay(self::applyPersonalDefaults($row ?: [], $userId));
	}

	public static function getPersonalForUser(int $userId): ?array
	{
		$row = (new \App\Db\Query())
			->from('u_yf_mail_accounts')
			->where(['kind' => 'personal', 'owner_user_id' => $userId])
			->one();
		return $row ? self::sanitizeForDisplay(self::applyPersonalDefaults($row, $userId)) : null;
	}

	public static function getUserAccounts(int $userId, bool $sendOnly = false): array
	{
		$accounts = [];
		$personal = self::getPersonalForUser($userId);
		if ($personal !== null && (!$sendOnly || (int) $personal['active'] === 1)) {
			$accounts[] = $personal;
		}
		$query = (new \App\Db\Query())
			->select(['a.*', 'au.is_default', 'au.can_send'])
			->from(['a' => 'u_yf_mail_accounts'])
			->innerJoin(['au' => 'u_yf_mail_account_users'], 'au.account_id = a.id')
			->where(['a.kind' => 'group', 'au.user_id' => $userId]);
		if ($sendOnly) {
			$query->andWhere(['au.can_send' => 1, 'a.active' => 1]);
		}
		foreach ($query->all() as $row) {
			$accounts[] = self::sanitizeForDisplay($row);
		}
		return $accounts;
	}

	/**
	 * @return list<array{ref: string, role: string, id: int, name: string, username: string, from_name: string, group_name: string}>
	 */
	public static function getComposeSenders(int $userId): array
	{
		$senders = [];
		$personal = self::getPersonalForUser($userId);
		if ($personal !== null && (int) ($personal['active'] ?? 0) === 1) {
			$senders[] = self::formatComposeSender($personal, 'personal', '');
		}
		$groupAccount = self::getGroupMailboxForUser($userId);
		if ($groupAccount !== null) {
			$groupName = (string) ((new \App\Db\Query())
				->select('groupname')
				->from('vtiger_groups')
				->where(['groupid' => (int) ($groupAccount['group_id'] ?? 0)])
				->scalar() ?: '');
			$senders[] = self::formatComposeSender($groupAccount, 'group', $groupName);
		}

		return $senders;
	}

	public static function getGroupMailboxForUser(int $userId): ?array
	{
		$row = (new \App\Db\Query())
			->select(['a.*'])
			->from(['a' => 'u_yf_mail_accounts'])
			->innerJoin(['au' => 'u_yf_mail_account_users'], 'au.account_id = a.id')
			->where([
				'a.kind' => 'group',
				'au.user_id' => $userId,
				'au.can_send' => 1,
				'a.active' => 1,
			])
			->andWhere(['not', ['a.group_id' => null]])
			->orderBy(['a.name' => SORT_ASC])
			->one();

		return $row ? self::sanitizeForDisplay($row) : null;
	}

	/**
	 * @return list<int>
	 */
	public static function getUserIdsForGroup(int $groupId): array
	{
		if ($groupId <= 0) {
			return [];
		}
		$focus = new \App\Utils\GetGroupUsers();
		$focus->getAllUsersInGroup($groupId);

		return array_values(array_unique(array_map('intval', $focus->group_users)));
	}

	public static function syncForGroup(int $groupId): void
	{
		if ($groupId <= 0) {
			return;
		}
		$accountIds = (new \App\Db\Query())
			->select(['id'])
			->from('u_yf_mail_accounts')
			->where(['kind' => 'group', 'group_id' => $groupId])
			->column();
		foreach ($accountIds as $accountId) {
			self::syncGroupMembers((int) $accountId, $groupId);
		}
	}

	public static function syncGroupMembers(int $accountId, int $groupId): void
	{
		if ($accountId <= 0 || $groupId <= 0) {
			return;
		}
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->delete('u_yf_mail_account_users', ['account_id' => $accountId])->execute();
		foreach (self::getUserIdsForGroup($groupId) as $userId) {
			if ($userId <= 0) {
				continue;
			}
			$db->createCommand()->insert('u_yf_mail_account_users', [
				'account_id' => $accountId,
				'user_id' => $userId,
				'can_send' => 1,
				'is_default' => 0,
			])->execute();
		}
	}

	/**
	 * @param array<string, mixed> $account
	 * @return array{ref: string, role: string, id: int, name: string, username: string, from_name: string, group_name: string}
	 */
	private static function formatComposeSender(array $account, string $role, string $groupName): array
	{
		$id = (int) ($account['id'] ?? 0);

		return [
			'ref' => 'account:' . $id,
			'role' => $role,
			'id' => $id,
			'name' => (string) ($account['name'] ?? $account['username'] ?? ''),
			'username' => (string) ($account['username'] ?? ''),
			'from_name' => (string) ($account['from_name'] ?? ''),
			'group_name' => $groupName,
		];
	}

	public static function getDefaultAccountId(int $userId): ?int
	{
		$id = (new \App\Db\Query())
			->select('account_id')
			->from('u_yf_mail_account_users')
			->where(['user_id' => $userId, 'is_default' => 1])
			->scalar();
		return $id ? (int) $id : null;
	}

	public static function getUserProfileEmail(int $userId): string
	{
		if ($userId <= 0) {
			return '';
		}

		return trim((string) (new \App\Db\Query())
			->select('email1')
			->from('vtiger_users')
			->where(['id' => $userId])
			->scalar());
	}

	public static function preparePersonalAccountData(int $userId, array $data, bool $requireEmail = false): array
	{
		$data = self::applyPersonalDefaults($data, $userId);
		$email = self::getUserProfileEmail($userId);
		if ($email !== '') {
			$data['username'] = $email;
			$data['name'] = $email;
		} elseif ($requireEmail) {
			throw new \App\Exceptions\AppException('LBL_MAIL_USER_EMAIL_REQUIRED');
		}

		return $data;
	}

	public static function savePersonalForUser(int $userId, array $data, bool $activate = false): array
	{
		$encryption = new \App\Security\Encryption();
		if (!$encryption->isActive()) {
			throw new \App\Exceptions\AppException('LBL_ENCRYPTION_NOT_ACTIVE');
		}

		$existing = (new \App\Db\Query())
			->from('u_yf_mail_accounts')
			->where(['kind' => 'personal', 'owner_user_id' => $userId])
			->one() ?: null;

		$password = trim((string) ($data['password'] ?? ''));
		$passwordEnc = null;
		if ($existing) {
			if ($password !== '' && $password !== self::passwordMask()) {
				$passwordEnc = $encryption->encrypt($password);
			} else {
				$passwordEnc = $existing['password_enc'];
			}
		} elseif ($password === '' || $password === self::passwordMask()) {
			throw new \App\Exceptions\AppException('LBL_MAIL_PASSWORD_REQUIRED');
		} else {
			$passwordEnc = $encryption->encrypt($password);
		}

		$row = self::buildRow(self::preparePersonalAccountData($userId, $data, true), 'personal', $userId, $passwordEnc, $existing, $activate);

		$db = \App\Db\Db::getInstance();
		if ($existing) {
			$db->createCommand()->update('u_yf_mail_accounts', $row, ['id' => $existing['id']])->execute();
			$accountId = (int) $existing['id'];
		} else {
			$db->createCommand()->insert('u_yf_mail_accounts', $row)->execute();
			$accountId = (int) $db->getLastInsertID();
			$db->createCommand()->insert('u_yf_mail_account_users', [
				'account_id' => $accountId,
				'user_id' => $userId,
				'can_send' => 1,
				'is_default' => 1,
			])->execute();
		}

		return self::getById($accountId) ?? [];
	}

	public static function saveGroup(array $data, ?int $accountId = null, array $userIds = [], bool $activate = false): array
	{
		$encryption = new \App\Security\Encryption();
		if (!$encryption->isActive()) {
			throw new \App\Exceptions\AppException('LBL_ENCRYPTION_NOT_ACTIVE');
		}

		$existing = $accountId ? ((new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $accountId])->one() ?: null) : null;
		$password = trim((string) ($data['password'] ?? ''));
		if ($existing) {
			$passwordEnc = ($password !== '' && $password !== self::passwordMask())
				? $encryption->encrypt($password)
				: $existing['password_enc'];
		} elseif ($password === '' || $password === self::passwordMask()) {
			throw new \App\Exceptions\AppException('LBL_MAIL_PASSWORD_REQUIRED');
		} else {
			$passwordEnc = $encryption->encrypt($password);
		}

		$row = self::buildRow($data, 'group', null, $passwordEnc, $existing, $activate);
		$row['owner_user_id'] = null;
		$groupId = (int) ($data['group_id'] ?? 0);
		$row['group_id'] = $groupId > 0 ? $groupId : null;
		$db = \App\Db\Db::getInstance();

		if ($existing) {
			$db->createCommand()->update('u_yf_mail_accounts', $row, ['id' => $accountId])->execute();
		} else {
			$db->createCommand()->insert('u_yf_mail_accounts', $row)->execute();
			$accountId = (int) $db->getLastInsertID();
		}

		if ($accountId) {
			if ($groupId > 0) {
				self::syncGroupMembers((int) $accountId, $groupId);
			} elseif ($userIds !== []) {
				$db->createCommand()->delete('u_yf_mail_account_users', ['account_id' => $accountId])->execute();
				foreach ($userIds as $uid) {
					$db->createCommand()->insert('u_yf_mail_account_users', [
						'account_id' => $accountId,
						'user_id' => (int) $uid,
						'can_send' => 1,
						'is_default' => 0,
					])->execute();
				}
			}
		}

		return self::getById((int) $accountId) ?? [];
	}

	public static function deleteAccount(int $accountId): void
	{
		\App\Db\Db::getInstance()->createCommand()->delete('u_yf_mail_accounts', ['id' => $accountId])->execute();
	}

	public static function getDecryptedPassword(array $account): string
	{
		$encryption = new \App\Security\Encryption();
		return (string) $encryption->decrypt((string) ($account['password_enc'] ?? ''));
	}

	public static function listAllForAdmin(): array
	{
		$groupNames = (new \App\Db\Query())
			->select(['groupid', 'groupname'])
			->from('vtiger_groups')
			->indexBy('groupid')
			->all();
		$userNames = (new \App\Db\Query())
			->select(['id', 'first_name', 'last_name'])
			->from('vtiger_users')
			->indexBy('id')
			->all();
		$rows = [];
		foreach ((new \App\Db\Query())->from('u_yf_mail_accounts')->orderBy(['kind' => SORT_ASC, 'name' => SORT_ASC])->all() as $row) {
			$display = self::sanitizeForDisplay($row);
			$gid = (int) ($display['group_id'] ?? 0);
			$display['group_name'] = $gid > 0 ? (string) ($groupNames[$gid]['groupname'] ?? '') : '';
			$uid = (int) ($display['owner_user_id'] ?? 0);
			if (($display['kind'] ?? '') === 'personal' && $uid > 0 && isset($userNames[$uid])) {
				$u = $userNames[$uid];
				$display['owner_name'] = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
			} else {
				$display['owner_name'] = '';
			}
			$rows[] = $display;
		}
		return $rows;
	}

	public static function getAccountsDueForScan(): array
	{
		return (new \App\Db\Query())
			->from('u_yf_mail_accounts')
			->where(['active' => 1])
			->andWhere(['or', ['next_scan_at' => null], ['<=', 'next_scan_at', date('Y-m-d H:i:s')]])
			->andWhere(['or', ['last_scan_status' => null], ['!=', 'last_scan_status', 'disabled']])
			->all();
	}

	public static function markScanResult(int $accountId, bool $ok, int $lastUid = 0, ?string $error = null): void
	{
		$maxFailures = (int) (\App\Core\AppConfig::module('Mail', 'max_consecutive_failures') ?? 5);
		$interval = (int) (\App\Core\AppConfig::module('Mail', 'default_scan_interval') ?? 120);
		$account = (new \App\Db\Query())->from('u_yf_mail_accounts')->where(['id' => $accountId])->one();
		$failures = $ok ? 0 : ((int) ($account['consecutive_failures'] ?? 0) + 1);
		$backoff = min($interval * max(1, $failures), 3600);

		\App\Db\Db::getInstance()->createCommand()->update('u_yf_mail_accounts', [
			'last_scan_at' => date('Y-m-d H:i:s'),
			'last_scan_status' => $ok ? 'ok' : 'error',
			'last_scan_error' => $error,
			'consecutive_failures' => $failures,
			'last_uid' => $lastUid > 0 ? $lastUid : ($account['last_uid'] ?? 0),
			'next_scan_at' => date('Y-m-d H:i:s', time() + $backoff),
			'active' => $failures >= $maxFailures ? 0 : ($account['active'] ?? 1),
		], ['id' => $accountId])->execute();
	}

	private static function getUserPersonalHints(int $userId): array
	{
		$user = (new \App\Db\Query())
			->select(['email1', 'first_name', 'last_name'])
			->from('vtiger_users')
			->where(['id' => $userId])
			->one();
		if (!$user) {
			return [];
		}

		$hints = [];
		if (!empty($user['email1'])) {
			$hints['username'] = $user['email1'];
		}
		$fromName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
		if ($fromName !== '') {
			$hints['from_name'] = $fromName;
		}

		return $hints;
	}

	private static function isEmptyAccountField(mixed $value): bool
	{
		return $value === null || $value === '';
	}

	public static function ensurePersonalAccountForUser(int $userId): void
	{
		if ($userId <= 0) {
			return;
		}

		$hints = self::getUserPersonalHints($userId);
		$email = trim((string) ($hints['username'] ?? ''));
		if ($email === '') {
			return;
		}

		$existing = (new \App\Db\Query())
			->from('u_yf_mail_accounts')
			->where(['kind' => 'personal', 'owner_user_id' => $userId])
			->one();

		if ($existing) {
			$update = [];
			if ((string) ($existing['username'] ?? '') !== $email) {
				$update['username'] = $email;
			}
			if ((string) ($existing['name'] ?? '') !== $email) {
				$update['name'] = $email;
			}
			$fromName = trim((string) ($hints['from_name'] ?? ''));
			if ($fromName !== '' && (string) ($existing['from_name'] ?? '') !== $fromName) {
				$update['from_name'] = $fromName;
			}
			if ($update !== []) {
				\App\Db\Db::getInstance()->createCommand()->update(
					'u_yf_mail_accounts',
					$update,
					['id' => (int) $existing['id']]
				)->execute();
			}

			return;
		}

		$data = self::applyPersonalDefaults(['username' => $email], $userId);
		$row = self::buildRow($data, 'personal', $userId, null, null, false);
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->insert('u_yf_mail_accounts', $row)->execute();
		$accountId = (int) $db->getLastInsertID();
		$db->createCommand()->insert('u_yf_mail_account_users', [
			'account_id' => $accountId,
			'user_id' => $userId,
			'can_send' => 1,
			'is_default' => 1,
		])->execute();
	}

	private static function buildRow(array $data, string $kind, ?int $ownerUserId, ?string $passwordEnc, ?array $existing, bool $activate): array
	{
		$groupId = (int) ($data['group_id'] ?? 0);
		$row = [
			'name' => trim((string) ($data['name'] ?? $data['username'] ?? '')),
			'kind' => $kind,
			'owner_user_id' => $ownerUserId,
			'group_id' => $kind === 'group' && $groupId > 0 ? $groupId : null,
			'imap_host' => trim((string) ($data['imap_host'] ?? '')),
			'imap_port' => (int) ($data['imap_port'] ?? 993),
			'imap_secure' => $data['imap_secure'] ?? 'ssl',
			'imap_validate_cert' => (int) ($data['imap_validate_cert'] ?? 1),
			'imap_folder_inbox' => trim((string) ($data['imap_folder_inbox'] ?? 'INBOX')),
			'imap_folder_sent' => $data['imap_folder_sent'] ?? null,
			'smtp_host' => trim((string) ($data['smtp_host'] ?? '')),
			'smtp_port' => (int) ($data['smtp_port'] ?? 465),
			'smtp_secure' => $data['smtp_secure'] ?? 'ssl',
			'username' => trim((string) ($data['username'] ?? '')),
			'password_enc' => $passwordEnc,
			'from_name' => $data['from_name'] ?? null,
			'reply_to_mode' => $data['reply_to_mode'] ?? 'same_as_from',
			'reply_to_address' => $data['reply_to_address'] ?? null,
			'append_sent' => (int) ($data['append_sent'] ?? 1),
		];
		if ($activate) {
			$row['active'] = 1;
			$row['last_scan_status'] = 'ok';
			$row['next_scan_at'] = date('Y-m-d H:i:s');
		} elseif (!$existing) {
			$row['active'] = 0;
		}
		return $row;
	}

	private static function sanitizeForDisplay(array $row): array
	{
		unset($row['password_enc']);
		$row['password'] = self::passwordMask();
		return $row;
	}
}
