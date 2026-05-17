<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Mapping step for ImportManager batches.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Views;

use App\Modules\ImportManager\Controllers\WizardController;

class Mapping extends \App\Modules\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$batchId = (int) $request->get('batch_id');
		if ($batchId <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$controller = new WizardController();
		$context = $controller->buildMappingContext($batchId, $request);

		$viewer = $this->getViewer($request);
		$viewer->assign('IMPORT_BATCH', $context['batch']);
		$viewer->assign('IMPORT_PREVIEW', $context['preview']);
		$viewer->assign('IMPORT_DEFINITION', $context['definition']);
		$viewer->assign('IMPORT_DUPLICATE_CONFIG', $context['duplicateConfig']);
		$viewer->assign('IMPORT_FIELDS', $context['fields']);
		$viewer->assign('IMPORT_HEADERS', $context['headers']);
		$viewer->assign('IMPORT_STEPS', $context['steps']);
		
		// Ensure JSON is valid - clean any non-serializable values
		$clientData = $this->cleanForJson($context['client']);
		$json = \App\Utils\Json::encode($clientData);
		if ($json === false) {
			\App\Log\Log::error('Failed to encode ImportManager context JSON: ' . json_last_error_msg());
			$json = '{}';
		}
		$viewer->assign('IMPORT_CONTEXT_JSON', $json);

		$viewer->view('Mapping.tpl', $request->getModule());
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$footerScripts = parent::getFooterScripts($request);
		$jsFileNames = [
			'layouts.basic.modules.ImportManager.resources.wizard',
		];
		$jsScripts = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($footerScripts, $jsScripts);
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCss = parent::getHeaderCss($request);
		$cssFileNames = [
			'layouts.basic.modules.ImportManager.resources.wizard',
		];
		$cssStyles = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCss, $cssStyles);
	}

	/**
	 * Clean data structure for JSON encoding
	 * Removes non-serializable values and ensures all data is JSON-safe
	 */
	private function cleanForJson($data)
	{
		if (is_array($data)) {
			$cleaned = [];
			foreach ($data as $key => $value) {
				$cleaned[$key] = $this->cleanForJson($value);
			}
			return $cleaned;
		} elseif (is_object($data)) {
			// Convert objects to arrays
			return $this->cleanForJson((array)$data);
		} elseif (is_resource($data)) {
			return null;
		} elseif (is_callable($data)) {
			return null;
		} else {
			return $data;
		}
	}
}


