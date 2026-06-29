<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Import history page listing the current user's batches.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Views;

use App\Modules\ImportManager\Controllers\WizardController;

class ListView extends \App\Modules\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$controller = new WizardController();
		$context = $controller->buildHistoryContext($request);

		$viewer = $this->getViewer($request);
		$viewer->assign('IMPORT_BATCHES', $context['batches']);
		$viewer->assign('IMPORT_CONTEXT_JSON', \App\Utils\Json::encode($context['client']));

		$viewer->view('History.tpl', $request->getModule());
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
