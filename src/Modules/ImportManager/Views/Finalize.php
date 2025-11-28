<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Final import step for ImportManager batches.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Views;

use App\Modules\ImportManager\Controllers\WizardController;

class Finalize extends \App\Modules\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$batchId = (int) $request->get('batch_id');
		if ($batchId <= 0) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$controller = new WizardController();
		$context = $controller->buildFinalizeContext($batchId);

		$viewer = $this->getViewer($request);
		$viewer->assign('IMPORT_BATCH', $context['batch']);
		$viewer->assign('IMPORT_STATS', $context['stats']);
		$viewer->assign('IMPORT_IMPORT_SUMMARY', $context['importSummary']);
		$viewer->assign('IMPORT_STEPS', $context['steps']);
		$viewer->assign('READY_INFO_TEXT', $context['readyInfoText'] ?? '');
		$viewer->assign('RESULT_MESSAGE_TEXT', $context['resultMessageText'] ?? null);
		$viewer->assign('IMPORT_CONTEXT_JSON', \App\Utils\Json::encode($context['client']));

		$viewer->view('Finalize.tpl', $request->getModule());
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
}


