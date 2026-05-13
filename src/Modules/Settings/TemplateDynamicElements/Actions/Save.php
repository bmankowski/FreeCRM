<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\Settings\TemplateDynamicElements\Actions;

/**
 * Save action for PDF dynamic elements.
 */
class Save extends \App\Modules\Settings\Base\Actions\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$recordModel = $recordId
			? \App\Modules\Settings\TemplateDynamicElements\Models\Record::getInstanceById($recordId)
			: \App\Modules\Settings\TemplateDynamicElements\Models\Record::getCleanInstance();

		$fields = ['label', 'type', 'module_name', 'language', 'sequence', 'description'];
		foreach ($fields as $field) {
			$recordModel->set($field, $request->get($field));
		}
		$type = (string) $recordModel->get('type');
		if (!in_array($type, \App\Modules\Settings\TemplateDynamicElements\Models\Module::getAllowedTypes(), true)) {
			throw new \App\Exceptions\AppException('Invalid dynamic element type');
		}
		$recordModel->set('status', $request->has('status') ? 1 : 0);
		if (\App\Modules\Settings\TemplateDynamicElements\Models\Record::isDocumentLayoutType($type)) {
			$recordModel->set('layout_header', $request->getForHtml('layout_header'));
			$recordModel->set('layout_body', $request->getForHtml('layout_body'));
			$recordModel->set('layout_footer', $request->getForHtml('layout_footer'));
			$recordModel->set('content', '');
		} else {
			$recordModel->set('content', $request->getForHtml('content'));
			$recordModel->set('layout_header', '');
			$recordModel->set('layout_body', '');
			$recordModel->set('layout_footer', '');
		}

		if (!$recordId) {
			$code = $this->normalizeCode((string) $request->get('code'));
			$recordModel->set('code', $code);
		}

		$recordModel->save();

		if ($request->isAjax()) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult(['success' => true, 'id' => $recordModel->getId(), 'url' => $recordModel->getEditViewUrl()]);
			$response->emit();
			return;
		}

		header('Location: ' . \App\Modules\Settings\TemplateDynamicElements\Models\Module::getDefaultUrl());
		exit;
	}

	protected function normalizeCode(string $code): string
	{
		$code = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $code);
		$code = strtolower((string) $code);
		$code = preg_replace('/[^a-z0-9]+/', '_', $code);
		return trim((string) $code, '_');
	}
}
