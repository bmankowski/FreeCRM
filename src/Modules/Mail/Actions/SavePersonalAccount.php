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

namespace App\Modules\Mail\Actions;

class SavePersonalAccount extends Base
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		$recordUserId = (int) $request->get('owner_user_id', $request->getUser()->getId());
		$currentUserId = (int) $request->getUser()->getId();
		if (!$request->getUser()->isAdminUser() && $recordUserId !== $currentUserId) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$userId = (int) $request->get('owner_user_id', $request->getUser()->getId());
		$data = $request->getAll();
		$testFirst = (bool) $request->get('activate', false);

		if ($testFirst) {
			$test = \App\Modules\Mail\Imap\Client::testConnection($data, $data['password'] ?? null);
			if (!$test['success']) {
				$response = new \App\Http\Vtiger_Response();
				$response->setResult(['success' => false, 'error' => $test['error'] ?? 'LBL_CONNECTION_FAILED']);
				$response->emit();
				return;
			}
		}

		$account = \App\Modules\Mail\Models\Account::savePersonalForUser($userId, $data, $testFirst);
		\App\Modules\Mail\Models\MailLog::write('save_personal', 'Personal account saved', 'info', (int) ($account['id'] ?? 0), $userId);

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['success' => true, 'account' => $account]);
		$response->emit();
	}
}
