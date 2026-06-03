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

class MailLog
{
	public static function write(string $action, string $message, string $level = 'info', ?int $accountId = null, ?int $userId = null, ?array $context = null): void
	{
		if ($context !== null) {
			unset($context['password'], $context['password_enc'], $context['body_html'], $context['body_text']);
		}
		\App\Db\Db::getInstance()->createCommand()->insert('u_yf_mail_log', [
			'account_id' => $accountId,
			'user_id' => $userId,
			'level' => $level,
			'action' => $action,
			'message' => mb_substr($message, 0, 500),
			'context_json' => $context ? json_encode($context) : null,
		])->execute();
		\App\Log\Log::info('[Mail] ' . $action . ': ' . $message);
	}
}
