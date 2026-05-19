<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\EmailTemplates\Views;

use App\Http\Vtiger_Request;

class VariablePanel extends \App\Modules\Base\Views\VariablePanel
{
	public function process(Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$selectedModule = trim((string) $request->get('selectedModule'));

		$dynamicElements = \App\Modules\TemplateElements\Models\Record::getActiveElements(
			$selectedModule !== '' ? $selectedModule : null,
			null
		);
		$variableAliases = array_values(array_filter($dynamicElements, static function (array $row): bool {
			return ($row['type'] ?? '') === 'PLL_VARIABLE_ALIAS';
		}));

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('SELECTED_MODULE', $selectedModule);
		$viewer->assign('PARSER_TYPE', $request->get('type'));
		$viewer->assign('GRAY', true);
		$viewer->assign('TEXT_PARSER', \App\TextParser\TextParser::getInstance($selectedModule ?: 'Vtiger'));
		$viewer->assign(
			'VARIABLE_PANEL_HAS_ENTITY_INFO',
			$selectedModule !== '' && (bool) \App\Utils\ModuleUtils::getEntityInfo($selectedModule)
		);
		$viewer->assign('QUALIFIED_SETTINGS_MODULE', 'DocumentTemplates');
		$viewer->assign('VARIABLE_PANEL_DYNAMIC_ALIASES', $variableAliases);
		$viewer->assign(
			'DYNAMIC_ELEMENTS_JSON',
			\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($dynamicElements))
		);
		$viewer->view('VariablePanel.tpl', $moduleName);
	}
}
