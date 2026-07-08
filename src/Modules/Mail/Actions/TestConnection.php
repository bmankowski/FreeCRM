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

class TestConnection extends Base
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$userId = (int) $request->getUser()->getId();
		$data = $request->getAll();
		$kind = $data['kind'] ?? 'personal';

		if ($kind === 'group' && !$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
		if ($kind === 'personal') {
			$ownerId = (int) ($data['owner_user_id'] ?? $userId);
			if (!$request->getUser()->isAdminUser() && $ownerId !== $userId) {
				throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
			}
			if (\App\Modules\Mail\Models\Account::getUserProfileEmail($ownerId) === '') {
				$response = new \App\Http\Vtiger_Response();
				$response->setResult(['success' => false, 'error' => 'LBL_MAIL_USER_EMAIL_REQUIRED']);
				$response->emit();
				return;
			}
		}

		$result = \App\Modules\Mail\Imap\Client::testConnection($data, $data['password'] ?? null);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
