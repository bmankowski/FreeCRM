<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * UI entry point for ImportManager wizard.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Views;

use App\Modules\ImportManager\Controllers\WizardController;

class Wizard extends \App\Modules\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$controller = new WizardController();
		$context = $controller->buildStepOneContext($request);

		$viewer = $this->getViewer($request);
		$viewer->assign('IMPORT_AVAILABLE_MODULES', $context['modules']);
		$viewer->assign('IMPORT_CONFIG', $context['config']);
		$viewer->assign('IMPORT_RECENT_BATCHES', $context['recentBatches']);
		$viewer->assign('IMPORT_SELECTED_MODULE', $context['selectedModule'] ?? null);
		$viewer->view('WizardStep1.tpl', $request->getModule());
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$jsFileNames = [
			'layouts.basic.modules.ImportManager.resources.wizard',
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = [
			'layouts.basic.modules.ImportManager.resources.wizard',
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		return array_merge($headerCssInstances, $cssInstances);
	}
}

