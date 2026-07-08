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

class Acl
{
	public const ACTION_VIEW = 'view';
	public const ACTION_SEND = 'send';
	public const ACTION_EDIT_ACCOUNT = 'edit_account';
	public const ACTION_ADMIN = 'admin';

	public static function assert(int $userId, string $action, array $context = []): void
	{
		if (!self::isAllowed($userId, $action, $context)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public static function isAllowed(int $userId, string $action, array $context = []): bool
	{
		/** @var \App\Modules\Users\Models\Record $user */
		$user = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
		if ($user->isAdminUser()) {
			return true;
		}

		return match ($action) {
			self::ACTION_ADMIN => false,
			self::ACTION_EDIT_ACCOUNT => self::canEditAccount($userId, $context['account'] ?? []),
			self::ACTION_SEND => self::canSend($userId, (int) ($context['account_id'] ?? 0)),
			self::ACTION_VIEW => self::canViewMessage($userId, $context),
			default => false,
		};
	}

	public static function canEditAccount(int $userId, array $account): bool
	{
		if ($account === []) {
			return true;
		}
		if (($account['kind'] ?? '') === 'personal') {
			return (int) ($account['owner_user_id'] ?? 0) === $userId;
		}
		return false;
	}

	public static function canSend(int $userId, int $accountId): bool
	{
		if ($accountId <= 0) {
			return false;
		}
		$account = Account::getById($accountId);
		if ($account === null) {
			return false;
		}
		if ((int) ($account['active'] ?? 0) !== 1) {
			return false;
		}
		if ($account['kind'] === 'personal') {
			return (int) $account['owner_user_id'] === $userId;
		}
		return (new \App\Db\Query())
			->from('u_yf_mail_account_users')
			->where(['account_id' => $accountId, 'user_id' => $userId, 'can_send' => 1])
			->exists();
	}

	public static function canViewMessage(int $userId, array $context): bool
	{
		/** @var \App\Modules\Users\Models\Record $user */
		$user = \App\Modules\Users\Models\Record::getInstanceById($userId, 'Users');
		if ($user->isAdminUser()) {
			return true;
		}
		$message = $context['message'] ?? [];
		$account = $context['account'] ?? null;
		if ($account === null && !empty($message['account_id'])) {
			$account = Account::getById((int) $message['account_id']);
		}
		if ($account === null || empty($message)) {
			return !empty($message['smtp_id']);
		}
		if ($account['kind'] === 'group') {
			return true;
		}
		if ($message['direction'] === 'out') {
			return (int) ($message['sender_user_id'] ?? 0) === $userId;
		}
		return (int) ($account['owner_user_id'] ?? 0) === $userId;
	}
}
