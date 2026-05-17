<?php

namespace App\Modules\TemplateElements\Actions;

class Save extends \App\Modules\Base\Actions\Save
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordModel = $this->saveRecord($request);
		if ($request->get('returnToList')) {
			$loadUrl = $recordModel->getModule()->getListViewUrl();
		} else {
			$params = [
				'module' => $request->getModule(),
				'view' => 'Edit',
				'record' => $recordModel->getId(),
			];
			if ($request->has('parent') && $request->get('parent') !== '') {
				$params['parent'] = $request->get('parent');
			}
			if ($request->has('mid') && $request->get('mid') !== '') {
				$params['mid'] = $request->get('mid');
			}
			$loadUrl = 'index.php?' . http_build_query($params);
		}
		$this->redirectUrl = $loadUrl;
		if ($request->get('mode') !== 'edit') {
			$request->set('record', $recordModel->getId());
		}

		if ($request->isAjax()) {
			$response = new \App\Http\Vtiger_Response();
			$response->setResult([
				'url' => $loadUrl,
				'record' => $recordModel->getId(),
				'success' => true,
			]);
			$response->emit();
			return;
		}

		header('Location: ' . $loadUrl);
		exit;
	}

	protected function getRecordModelFromRequest(\App\Http\Vtiger_Request $request)
	{
		$recordModel = parent::getRecordModelFromRequest($request);
		$type = (string) $recordModel->get('type');
		if (!in_array($type, \App\Modules\TemplateElements\Models\Module::getAllowedTypes(), true)) {
			throw new \App\Exceptions\AppException('Invalid template element type');
		}
		if (\App\Modules\TemplateElements\Models\Record::isDocumentLayoutType($type)) {
			if ($request->has('layout_header')) {
				$recordModel->set('layout_header', $request->getForHtml('layout_header', null));
			}
			if ($request->has('layout_body')) {
				$recordModel->set('layout_body', $request->getForHtml('layout_body', null));
			}
			if ($request->has('layout_footer')) {
				$recordModel->set('layout_footer', $request->getForHtml('layout_footer', null));
			}
			$recordModel->set('content', '');
		} elseif ($request->has('content')) {
			$recordModel->set('content', $request->getForHtml('content', null));
			$recordModel->set('layout_header', '');
			$recordModel->set('layout_body', '');
			$recordModel->set('layout_footer', '');
		}
		if ($recordModel->isNew()) {
			$code = $this->normalizeCode((string) $request->get('code'));
			if ($code === '') {
				$code = \App\Modules\TemplateElements\Models\Record::generateCode((string) $recordModel->get('label'));
			}
			$recordModel->set('code', $code);
		}
		if (!$request->has('status')) {
			$recordModel->set('status', 0);
		}
		return $recordModel;
	}

	protected function normalizeCode(string $code): string
	{
		$code = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $code);
		$code = strtolower((string) $code);
		$code = preg_replace('/[^a-z0-9]+/', '_', $code);
		return trim((string) $code, '_');
	}
}
