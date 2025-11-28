<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Upload step for the ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Views;

use App\Modules\ImportManager\Controllers\WizardController;

class Upload extends \App\Modules\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$controller = new WizardController();
		$context = $controller->buildUploadContext($request);

		$viewer = $this->getViewer($request);
		$viewer->assign('IMPORT_AVAILABLE_MODULES', $context['modules']);
		$viewer->assign('IMPORT_CONFIG', $context['config']);
		$viewer->assign('IMPORT_RECENT_BATCHES', $context['recentBatches']);
		$viewer->assign('IMPORT_SELECTED_MODULE', $context['selectedModule']);
		$viewer->assign('IMPORT_STEPS', $context['steps']);
		$viewer->assign('IMPORT_CONTEXT_JSON', \App\Utils\Json::encode($context['client']));

		$viewer->view('Upload.tpl', $request->getModule());
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


