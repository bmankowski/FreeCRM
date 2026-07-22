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

namespace App\Modules\Settings\AiPrompts\Views;

use App\Modules\Settings\AiPrompts\Models\ProviderConfig;

class Provider extends \App\Modules\Settings\Base\Views\Index
{
	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!$request->getUser()->isAdminUser()) {
			throw new \App\Exceptions\NoPermittedForAdmin('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$moduleName = $request->getModule(false);
		$config = ProviderConfig::get();
		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->assign('PROVIDER_CONFIG', $config);
		$viewer->assign('MASKED_KEY', ProviderConfig::maskedKeyHint($config['has_api_key']));
		$viewer->assign('SUGGESTED_MODELS', ProviderConfig::suggestedModels());
		$viewer->view('Provider.tpl', $moduleName);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			'modules.Settings.Vtiger.resources.Index',
			"modules.Settings.$moduleName.resources.Provider",
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);

		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
