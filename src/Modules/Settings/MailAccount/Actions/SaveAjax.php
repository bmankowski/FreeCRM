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
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$data = $request->get('param') ?? $request->getAll();
		$recordId = (int) ($data['record'] ?? $data['id'] ?? 0);
		$recordModel = $recordId
			? \App\Modules\Settings\MailAccount\Models\Record::getInstanceById($recordId)
			: \App\Modules\Settings\MailAccount\Models\Record::getCleanInstance();
		if ($recordId && $recordModel === null) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}

		foreach ($data as $key => $value) {
			$recordModel->set($key, $value);
		}

		if (!empty($data['test_connection'])) {
			$test = \App\Modules\Mail\Imap\Client::testConnection(array_merge($recordModel->getData(), ['id' => $recordId]), $data['password'] ?? null);
			if (!$test['success']) {
				$response = new \App\Http\Vtiger_Response();
				$response->setResult(['success' => false, 'message' => $test['error'] ?? 'LBL_CONNECTION_FAILED']);
				$response->emit();
				return;
			}
			$recordModel->set('activate', true);
			if (empty(trim((string) ($data['imap_folder_sent'] ?? ''))) && !empty($test['suggested_sent'])) {
				$recordModel->set('imap_folder_sent', $test['suggested_sent']);
			}
		}

		$recordModel->save();
		$response = new \App\Http\Vtiger_Response();
		$result = [
			'success' => true,
			'url' => $recordModel->getEditViewUrl(),
		];
		if (!empty($data['test_connection']) && !empty($test['success'])) {
			$result['folders'] = $test['folders'] ?? [];
			$result['folder_tree'] = $test['folder_tree'] ?? [];
			$result['suggested_sent'] = $test['suggested_sent'] ?? null;
		}
		$response->setResult($result);
		$response->emit();
	}
}
