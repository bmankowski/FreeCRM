<?php

namespace App\Modules\DocumentTemplates\Views;



/**
 * Edit View Class for PDF Settings
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Maciej Stencel <m.stencel@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class Edit extends \App\Modules\Base\Views\Basic
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$step = strtolower($request->getMode());
		$this->step($step, $request);
	}

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');
		if ($recordId) {
			try {
				$pdfModel = \App\Modules\DocumentTemplates\Models\DocumentTemplate::getInstanceById($recordId);
				$name = $pdfModel->getName();
				if ($name !== '') {
					return $name;
				}
			} catch (\Throwable $e) {
				return \App\Runtime\Vtiger_Language_Handler::translate('LBL_EDITING_TEMPLATE', $qualifiedModuleName);
			}
			return \App\Runtime\Vtiger_Language_Handler::translate('LBL_EDITING_TEMPLATE', $qualifiedModuleName);
		}
		return \App\Runtime\Vtiger_Language_Handler::translate('LBL_CREATING_TEMPLATE', $qualifiedModuleName);
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		$viewer = $this->getViewer($request);
		$mode = strtolower($request->getMode());
		if (!preg_match('/^step[1-6]$/', $mode)) {
			$mode = 'step1';
		}
		$viewer->assign('RECORD_MODE', $request->getMode());
		$viewer->assign('CURRENT_STEP', $mode);
		// MainLayout handles rendering, no separate preProcess template needed
	}

	public function step($step, \App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$template = 'Step1.tpl';
		$currentStep = preg_match('/^step[1-6]$/', $step) ? $step : 'step1';
		$viewer->assign('CURRENT_STEP', $currentStep);

		$recordId = $request->get('record');
		if ($recordId) {
			$pdfModel = \App\Modules\DocumentTemplates\Models\DocumentTemplate::getInstanceById($recordId);
			$viewer->assign('RECORDID', $recordId);
			$viewer->assign('MODE', 'edit');
			$selectedModuleName = $pdfModel->get('module_name');
		} else {
			$viewer->assign('RECORDID', '');
			$selectedModuleName = $request->get('source_module');
			$pdfModel = \App\Modules\DocumentTemplates\Models\Record::getCleanInstance($selectedModuleName ?: 'Vtiger');
		}
		$viewer->assign('SELECTED_MODULE', $selectedModuleName);
		$viewer->assign('PDF_MODEL', $pdfModel);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('SOURCE_MODULE', $selectedModuleName);
		switch ($step) {
			case 'step6':
				$this->preparePDFWatermarkData($viewer, $pdfModel);
				$template = 'Step6.tpl';
				break;

			case 'step5':
				$this->preparePDFPermissionsData($viewer);
				$template = 'Step5.tpl';
				break;

			case 'step4':
				$moduleModel = \App\Modules\Base\Models\Module::getInstance($pdfModel->get('module_name'));
				$recordStructureInstance = \App\Modules\Base\Models\RecordStructure::getInstanceForModule($moduleModel);
				$viewer->assign('MODULE_MODEL', $moduleModel);
				$viewer->assign('RECORD_STRUCTURE', $recordStructureInstance->getStructure());
				$viewer->assign('ADVANCE_CRITERIA', \App\Modules\Base\Helpers\AdvancedFilter::transformToAdvancedFilterCondition($pdfModel->get('conditions')));
				$viewer->assign('DATE_FILTERS', \App\Modules\Base\Helpers\AdvancedFilter::getDateFilter($pdfModel->get('module_name')));
				$viewer->assign('ADVANCED_FILTER_OPTIONS', \App\Modules\Base\Helpers\AdvancedFilter::getAdvancedFilterOptions());
				$viewer->assign('ADVANCED_FILTER_OPTIONS_BY_TYPE', \App\Modules\Base\Helpers\AdvancedFilter::getAdvancedFilterOpsByFieldType());
				$viewer->assign('FIELD_EXPRESSIONS', \App\Modules\Base\Helpers\AdvancedFilter::getExpressions());
				$viewer->assign('META_VARIABLES', \App\Modules\Base\Helpers\AdvancedFilter::getMetaVariables());
				$template = 'Step4.tpl';
				break;

			case 'step3':
				$insertOperations = [
					'PAGENO' => 'PAGENO',
					'PAGENUM' => 'nb'
				];
				$dynamicElements = \App\Modules\TemplateElements\Models\Record::getActiveElements($selectedModuleName, $pdfModel->get('language') ?: null);
				$variableAliases = array_values(array_filter($dynamicElements, static function (array $row): bool {
					return ($row['type'] ?? '') === 'PLL_VARIABLE_ALIAS';
				}));
				$viewer->assign('INSERT', $insertOperations);
				$viewer->assign('DYNAMIC_ELEMENTS', $dynamicElements);
				$viewer->assign('VARIABLE_PANEL_DYNAMIC_ALIASES', $variableAliases);
				$viewer->assign('DYNAMIC_ELEMENTS_JSON', \App\Modules\Base\Helpers\Util::toSafeHTML(\App\Utils\Json::encode($dynamicElements)));
				$this->preparePDFVariablePanelData($viewer, $selectedModuleName);
				$template = 'Step3.tpl';
				break;

			case 'step2':
				// Prepare languages list for template
				$languages = [];
				$query = (new \App\Db\Query())->from('vtiger_language')->select('prefix,label')->where(['active' => 1]);
				$dataReader = $query->createCommand()->query();
				while ($row = $dataReader->read()) {
					$languages[$row['prefix']] = $row['label'];
				}
				asort($languages);
				$viewer->assign('PAGE_FORMATS', \App\Modules\DocumentTemplates\Models\Module::getPageFormats());
				$viewer->assign('LANGUAGES', $languages);
				$docLayouts = \App\Modules\TemplateElements\Models\Record::getActiveDocumentLayouts(
					$selectedModuleName,
					(string) ($pdfModel->get('language') ?? '')
				);
				$viewer->assign('DOCUMENT_LAYOUT_OPTIONS', $docLayouts);
				$h = trim((string) $pdfModel->get('header_content'));
				$b = trim((string) $pdfModel->get('body_content'));
				$f = trim((string) $pdfModel->get('footer_content'));
				$viewer->assign('STEP2_HAS_TEMPLATE_CONTENT', ($h !== '' || $b !== '' || $f !== ''));
				$template = 'Step2.tpl';
				break;

			case 'step1':
			default:
				$allModules = \App\Modules\DocumentTemplates\Models\Module::getSupportedModules();
				$viewer->assign('ALL_MODULES', $allModules);
				break;
		}
		if ($request->isAjax()) {
			$viewer->view($template, $qualifiedModuleName);
			return;
		}
		$viewer->assign('STEP_TEMPLATE', $template);
		$viewer->view('Edit.tpl', $qualifiedModuleName);
	}
	
	/**
	 * Prepare data for PDF permissions template.
	 */
	protected function preparePDFPermissionsData($viewer)
	{
		$viewer->assign('ALL_GROUP_MEMBERS', \App\Modules\Settings\Groups\Models\Member::getAll(false));
	}

	/**
	 * Prepare data for PDF watermark template.
	 *
	 * @param \App\Runtime\CRM_Viewer $viewer
	 * @param \App\Modules\Base\Models\PDF $pdfModel
	 */
	protected function preparePDFWatermarkData($viewer, $pdfModel)
	{
		$viewer->assign('WATERMARK_TEXT', \App\Modules\Base\Models\PDF::WATERMARK_TYPE_TEXT);
		$viewer->assign('WATERMARK_TYPES', $pdfModel->getWatermarkType());
		$viewer->assign('WATERMARK_SIZE', (int) $pdfModel->get('watermark_size'));
		$viewer->assign('WATERMARK_ANGLE', (int) $pdfModel->get('watermark_angle'));
	}

	/**
	 * Prepare data for the shared variable panel used by PDF content steps.
	 *
	 * @param \App\Runtime\CRM_Viewer $viewer
	 * @param string $selectedModuleName
	 */
	protected function preparePDFVariablePanelData($viewer, $selectedModuleName)
	{
		$viewer->assign('TEXT_PARSER', \App\TextParser\TextParser::getInstance($selectedModuleName));
		$viewer->assign('VARIABLE_PANEL_HAS_ENTITY_INFO', (bool) \App\Utils\ModuleUtils::getEntityInfo($selectedModuleName));
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();

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
			'modules.Base.resources.Edit',
			"modules.$moduleName.resources.Edit",
			"modules.$moduleName.resources.Edit1",
			"modules.$moduleName.resources.Edit2",
			"modules.$moduleName.resources.Edit3TemplateGeneratorFooter",
			"modules.$moduleName.resources.Edit4",
			"modules.$moduleName.resources.Edit5",
			"modules.$moduleName.resources.Edit6",
			'modules.Base.resources.AdvanceFilter',
			'modules.Base.resources.AdvanceFilterEx',
			'modules.Base.resources.TemplateEditor',
		];

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$moduleName = $request->getModule();
		$cssFileNames = [
			'libraries.codemirror.lib.codemirror',
			'libraries.codemirror.addon.dialog.dialog',
			'modules.Base.resources.TemplateEditor',
			"modules.$moduleName.Edit",
		];
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($cssInstances, $headerCssInstances);
		return $headerCssInstances;
	}
}
