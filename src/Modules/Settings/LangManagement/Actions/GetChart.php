<?php

namespace App\Modules\Settings\LangManagement\Actions;
use App\Modules\Settings\LangManagement\Models\Module as Settings_LangManagement_Module_Model;



/**
 * GetChart Action Class for LangManagement Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class GetChart extends \App\Modules\Settings\Vtiger\Actions\Basic
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$langBase = $request->get('langBase');
		$langs = $request->get('langs');
		$tpl = $request->get('tpl');
		$modules = [];
		$data = [];
		if (!empty($langs) && $langs !== $langBase) {
			$moduleModel = Settings_LangManagement_Module_Model::getInstance($qualifiedModuleName);
			$modules = $moduleModel->getModFromLang($langBase);
			$data = $moduleModel->getStatsData($langBase, $langs);
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult([
			'success' => true,
			'data' => $data,
			'modules' => $modules
		]);
		$response->emit();
	}
}
