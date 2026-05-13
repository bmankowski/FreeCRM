<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\Settings\TemplateDynamicElements\Views;

/**
 * Edit view for PDF dynamic elements.
 */
class Edit extends \App\Modules\Settings\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule(false);
		$recordId = $request->get('record');
		$recordModel = $recordId
			? \App\Modules\Settings\TemplateDynamicElements\Models\Record::getInstanceById($recordId)
			: \App\Modules\Settings\TemplateDynamicElements\Models\Record::getCleanInstance();

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->assign('TYPE_SELECT_GROUPS', \App\Modules\Settings\TemplateDynamicElements\Models\Module::getTypeSelectGroups());
		$viewer->assign('LANGUAGES', \App\Runtime\Vtiger_Language_Handler::getAllLanguages());
		$this->assignVariablePanelData($viewer, $recordModel);
		$viewer->view('Edit.tpl', $moduleName);
	}

	/**
	 * Data for Base/VariablePanel.tpl (same building blocks as PDF template step 3).
	 */
	protected function assignVariablePanelData(\App\Runtime\CRM_Viewer $viewer, \App\Modules\Settings\TemplateDynamicElements\Models\Record $recordModel): void
	{
		$scopeModule = trim((string) $recordModel->get('module_name'));
		$recordLanguage = trim((string) $recordModel->get('language'));
		$languageParam = $recordLanguage !== '' ? $recordLanguage : null;

		$viewer->assign('SELECTED_MODULE', $scopeModule);
		$viewer->assign('TEXT_PARSER', \App\TextParser\TextParser::getInstance($scopeModule));
		$viewer->assign(
			'VARIABLE_PANEL_HAS_ENTITY_INFO',
			$scopeModule !== '' && (bool) \App\Utils\ModuleUtils::getEntityInfo($scopeModule)
		);
		$templateModuleModel = \App\Modules\Settings\Base\Models\Module::getInstance('Settings:Template');
		$viewer->assign('QUALIFIED_SETTINGS_MODULE', $templateModuleModel->getName(true));

		$dynamicElements = \App\Modules\Settings\TemplateDynamicElements\Models\Record::getActiveElements(
			$scopeModule !== '' ? $scopeModule : null,
			$languageParam
		);
		$variableAliases = array_values(array_filter($dynamicElements, static function (array $row): bool {
			return ($row['type'] ?? '') === 'PLL_VARIABLE_ALIAS';
		}));
		$currentId = (int) $recordModel->getId();
		if ($currentId > 0) {
			$variableAliases = array_values(array_filter($variableAliases, static function (array $row) use ($currentId): bool {
				return (int) ($row['dynamicid'] ?? 0) !== $currentId;
			}));
		}
		$viewer->assign('VARIABLE_PANEL_DYNAMIC_ALIASES', $variableAliases);
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$scripts = parent::getFooterScripts($request);
		$jsFileNames = [
			'libraries.jquery.clipboardjs.clipboard',
			'libraries.codemirror.lib.codemirror',
			'libraries.codemirror.mode.xml.xml',
			'libraries.codemirror.mode.javascript.javascript',
			'libraries.codemirror.mode.css.css',
			'libraries.codemirror.mode.htmlmixed.htmlmixed',
			'libraries.codemirror.addon.edit.matchbrackets',
			'libraries.codemirror.addon.edit.closebrackets',
			'libraries.codemirror.addon.edit.closetag',
			'libraries.codemirror.addon.selection.active-line',
			'modules.Settings.Vtiger.resources.Edit',
			"modules.Settings.{$request->getModule()}.resources.Edit",
		];
		return array_merge($scripts, $this->checkAndConvertJsScripts($jsFileNames));
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$styles = parent::getHeaderCss($request);
		$cssFileNames = [
			'libraries.codemirror.lib.codemirror',
		];
		return array_merge($this->checkAndConvertCssStyles($cssFileNames), $styles);
	}
}
