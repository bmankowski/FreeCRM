<?php

namespace App\Modules\TemplateElements\Views;

class Edit extends \App\Modules\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$recordModel = $recordId
			? \App\Modules\TemplateElements\Models\Record::getInstanceById($recordId, $moduleName)
			: \App\Modules\TemplateElements\Models\Record::getCleanInstance($moduleName);

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $moduleName);
		$viewer->assign('TYPE_SELECT_GROUPS', \App\Modules\TemplateElements\Models\Module::getTypeSelectGroups());
		$viewer->assign('LANGUAGES', \App\Runtime\Vtiger_Language_Handler::getAllLanguages());
		$this->assignVariablePanelData($viewer, $recordModel);
		$viewer->view('Edit.tpl', $moduleName);
	}

	protected function assignVariablePanelData(\App\Runtime\CRM_Viewer $viewer, \App\Modules\TemplateElements\Models\Record $recordModel): void
	{
		$scopeModule = trim((string) $recordModel->get('module_name'));
		$recordLanguage = trim((string) $recordModel->get('language'));
		$languageParam = $recordLanguage !== '' ? $recordLanguage : null;

		$viewer->assign('SELECTED_MODULE', $scopeModule);
		$viewer->assign('TEXT_PARSER', \App\TextParser\TextParser::getInstance($scopeModule ?: 'Vtiger'));
		$viewer->assign(
			'VARIABLE_PANEL_HAS_ENTITY_INFO',
			$scopeModule !== '' && (bool) \App\Utils\ModuleUtils::getEntityInfo($scopeModule)
		);
		$viewer->assign('QUALIFIED_SETTINGS_MODULE', 'DocumentTemplates');

		$dynamicElements = \App\Modules\TemplateElements\Models\Record::getActiveElements(
			$scopeModule !== '' ? $scopeModule : null,
			$languageParam
		);
		$variableAliases = array_values(array_filter($dynamicElements, static function (array $row): bool {
			return ($row['type'] ?? '') === 'PLL_VARIABLE_ALIAS';
		}));
		$currentId = (int) $recordModel->getId();
		if ($currentId > 0) {
			$variableAliases = array_values(array_filter($variableAliases, static function (array $row) use ($currentId): bool {
				return (int) ($row['templateelementsid'] ?? 0) !== $currentId;
			}));
		}
		$viewer->assign('VARIABLE_PANEL_DYNAMIC_ALIASES', $variableAliases);
		$viewer->assign(
			'DYNAMIC_ELEMENTS_JSON',
			\App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($dynamicElements))
		);
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
			'libraries.codemirror.addon.dialog.dialog',
			'libraries.codemirror.addon.search.searchcursor',
			'libraries.codemirror.addon.search.search',
			'~libraries/js-beautify/beautify-html.min.js',
			'modules.Base.resources.TemplateEditor',
			'modules.TemplateElements.resources.Edit',
		];
		return array_merge($scripts, $this->checkAndConvertJsScripts($jsFileNames));
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$styles = parent::getHeaderCss($request);
		$cssFileNames = [
			'libraries.codemirror.lib.codemirror',
			'libraries.codemirror.addon.dialog.dialog',
			'modules.Base.resources.TemplateEditor',
		];
		return array_merge($this->checkAndConvertCssStyles($cssFileNames), $styles);
	}
}
