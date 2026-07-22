<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Settings\AiPrompts\Actions;

class SaveAjax extends \App\Modules\Settings\Base\Views\IndexAjax
{
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function save(\App\Http\Vtiger_Request $request): void
	{
		$data = $request->get('param') ?? [];
		$recordId = (int) ($data['record'] ?? 0);

		if ($recordId > 0) {
			$recordModel = \App\Modules\Settings\AiPrompts\Models\Record::getInstanceById($recordId);
			if ($recordModel === null) {
				$this->emitResult(false, 'LBL_RECORD_NOT_FOUND');

				return;
			}
		} else {
			$recordModel = \App\Modules\Settings\AiPrompts\Models\Record::getCleanInstance();
		}

		$recordModel->set('action_key', (string) ($data['action_key'] ?? ''));
		$recordModel->set('name', (string) ($data['name'] ?? ''));
		$recordModel->set('prompt_body', (string) ($data['prompt_body'] ?? ''));
		$recordModel->set('active', !empty($data['active']) ? 1 : 0);

		try {
			$recordModel->save();
		} catch (\InvalidArgumentException|\RuntimeException $e) {
			$message = $e->getMessage();
			$translated = \App\Runtime\Vtiger_Language_Handler::translate($message, 'Settings:AiPrompts');
			$this->emitResult(false, $translated !== $message ? $translated : $message);

			return;
		}

		$this->emitResult(true, '', $recordModel->getDetailViewUrl());
	}

	private function emitResult(bool $success, string $message = '', string $url = ''): void
	{
		$response = new \App\Http\Vtiger_Response();
		$result = ['success' => $success];
		if ($message !== '') {
			$result['message'] = $message;
		}
		if ($url !== '') {
			$result['url'] = $url;
		}
		$response->setResult($result);
		$response->emit();
	}
}
