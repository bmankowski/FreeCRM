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

namespace App\Modules\Settings\MailAccount\Actions;

class SaveAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{
	/**
	 * @return list<string>
	 */
	private static function accountFields(): array
	{
		return [
			'kind', 'name', 'group_id', 'imap_host', 'imap_port', 'imap_secure',
			'imap_validate_cert', 'imap_folder_inbox', 'imap_folder_sent', 'smtp_host', 'smtp_port',
			'smtp_secure', 'username', 'password', 'from_name', 'reply_to_mode', 'reply_to_address',
			'append_sent', 'activate',
		];
	}

	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$data = $request->get('param') ?? $request->getAll();
		$kind = (string) ($data['kind'] ?? 'group');
		if ($kind === 'personal') {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(['success' => false, 'message' => 'LBL_PERSONAL_ACCOUNT_USE_MAIL_SAVE']);
			$response->emit();
			return;
		}

		$recordId = (int) ($data['record'] ?? $data['id'] ?? 0);
		$recordModel = $recordId
			? \App\Modules\Settings\MailAccount\Models\Record::getInstanceById($recordId)
			: \App\Modules\Settings\MailAccount\Models\Record::getCleanInstance();
		if ($recordId && $recordModel === null) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		if ($recordId) {
			$recordModel->set('id', $recordId);
		}

		foreach (self::accountFields() as $key) {
			if (array_key_exists($key, $data)) {
				$recordModel->set($key, $data[$key]);
			}
		}

		$groupId = (int) ($data['group_id'] ?? 0);
		if ($groupId <= 0) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(['success' => false, 'message' => 'LBL_CRM_GROUP_REQUIRED']);
			$response->emit();
			return;
		}

		$replyToMode = (string) ($recordModel->get('reply_to_mode') ?? 'same_as_from');
		if (!\in_array($replyToMode, ['same_as_from', 'user_personal', 'custom'], true)) {
			$replyToMode = 'same_as_from';
			$recordModel->set('reply_to_mode', $replyToMode);
		}
		if ($replyToMode === 'custom') {
			$replyToAddress = trim((string) ($recordModel->get('reply_to_address') ?? ''));
			if ($replyToAddress === '' || !filter_var($replyToAddress, FILTER_VALIDATE_EMAIL)) {
				$response = new \App\Http\Vtiger_Response();
				$response->setResult(['success' => false, 'message' => 'LBL_REPLY_TO_ADDRESS_REQUIRED']);
				$response->emit();
				return;
			}
			$recordModel->set('reply_to_address', $replyToAddress);
		} else {
			$recordModel->set('reply_to_address', null);
		}

		$recordModel->save();
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'url' => $recordModel->getEditViewUrl(),
		]);
		$response->emit();
	}
}
