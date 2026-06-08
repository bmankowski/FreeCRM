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

namespace App\Modules\LinkAction\Services;

use App\Db\Query;

final class LinkActionLog
{
	public static function existsByJti(string $jti): bool
	{
		return (new Query())
			->from('u_yf_link_action_log')
			->where(['jti' => $jti])
			->exists();
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function insert(string $token, array $payload, ?string $clickedAt = null): void
	{
		$tokenService = new LinkActionToken();
		$processedAt = gmdate('Y-m-d H:i:s');
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->insert('u_yf_link_action_log', [
			'jti' => (string) ($payload['jti'] ?? ''),
			'kid' => (string) ($payload['kid'] ?? ''),
			'module' => (string) ($payload['module'] ?? ''),
			'record_id' => (int) ($payload['record_id'] ?? 0),
			'mail_message_id' => isset($payload['mid']) ? (int) $payload['mid'] : null,
			'action' => (string) ($payload['action'] ?? ''),
			'scope' => (string) ($payload['scope'] ?? ''),
			'email_field' => (string) ($payload['email_field'] ?? ''),
			'eh' => (string) ($payload['eh'] ?? ''),
			'token_fp' => $tokenService->tokenFingerprint($token),
			'processed_at' => $processedAt,
			'clicked_at' => $clickedAt ?? $processedAt,
		])->execute();
	}

	public static function parseQueueTimestamp(mixed $ts): ?string
	{
		if (!is_string($ts) || $ts === '') {
			return null;
		}
		try {
			return (new \DateTimeImmutable($ts))
				->setTimezone(new \DateTimeZone('UTC'))
				->format('Y-m-d H:i:s');
		} catch (\Exception) {
			return null;
		}
	}
}
