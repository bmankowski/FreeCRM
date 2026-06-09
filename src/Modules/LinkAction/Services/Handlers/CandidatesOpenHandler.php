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

namespace App\Modules\LinkAction\Services\Handlers;

use App\Modules\LinkAction\Services\LinkActionConfig;
use App\Modules\LinkAction\Services\LinkActionToken;

final class CandidatesOpenHandler implements HandlerInterface
{
	public function supports(string $moduleName, string $action, string $scope): bool
	{
		return $moduleName === 'Candidates'
			&& $action === 'open'
			&& LinkActionConfig::isActionAllowed($moduleName, $action, $scope);
	}

	public function handle(array $payload): void
	{
		$moduleName = (string) ($payload['module'] ?? '');
		$recordId = (int) ($payload['record_id'] ?? 0);
		$emailField = (string) ($payload['email_field'] ?? '');
		$expectedHash = (string) ($payload['eh'] ?? '');

		if ($recordId <= 0 || !LinkActionConfig::isEmailFieldAllowed($moduleName, $emailField)) {
			throw new \RuntimeException('Invalid Candidates open payload');
		}

		$record = \App\Modules\Base\Models\Record::getInstanceById($recordId, $moduleName);
		if (!$record->getId()) {
			throw new \RuntimeException('Candidates record not found: ' . $recordId);
		}

		$email = (string) $record->get($emailField);
		$tokenService = new LinkActionToken();
		$actualHash = $tokenService->emailHash($moduleName, $recordId, $emailField, $email);
		if (!hash_equals($expectedHash, $actualHash)) {
			throw new \RuntimeException('Email hash mismatch for Candidates record ' . $recordId);
		}

		$mailMessageId = (int) ($payload['mid'] ?? 0);
		if ($mailMessageId <= 0) {
			throw new \RuntimeException('Open tracking requires CRM mail message id');
		}
		$mailRow = \App\Modules\Mail\Models\Message::getById($mailMessageId);
		if (!$mailRow || ($mailRow['direction'] ?? '') !== 'out') {
			throw new \RuntimeException('CRM mail message not found: ' . $mailMessageId);
		}
	}
}
