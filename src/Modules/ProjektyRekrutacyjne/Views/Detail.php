<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

namespace App\Modules\ProjektyRekrutacyjne\Views;

/**
 * Detail view — dla podsumowania rekordu dołącza utility Bootstrap 5 (np. widżet kanban kandydatów),
 * bez zastępowania globalnego Bootstrapa 3 w całej aplikacji.
 */
class Detail extends \App\Modules\Base\Views\Detail
{
	/**
	 * @param \App\Http\Vtiger_Request $request
	 * @return \App\View\Assets\StyleAsset[]
	 */
	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$relatedListCss = $this->checkAndConvertCssStyles([
			'modules.ProjektyRekrutacyjne.resources.RelatedList',
			'modules.ProjektyRekrutacyjne.resources.RecruitmentProjectKanban',
			'modules.ProjektyRekrutacyjne.resources.JobAdvertisementLinks',
		]);
		$headerCssInstances = array_merge($headerCssInstances, $relatedListCss);
		if ($this->shouldIncludeBootstrap5Utilities($request)) {
			$bs5Utilities = $this->checkAndConvertCssStyles([
				'~libraries/bootstrap5/css/bootstrap-utilities.min.css',
			]);
			$headerCssInstances = array_merge($bs5Utilities, $headerCssInstances);
		}

		return $headerCssInstances;
	}

	/**
	 * Tylko widok szczegółów z aktywną zakładką „Podsumowanie” (requestMode=summary).
	 */
	protected function shouldIncludeBootstrap5Utilities(\App\Http\Vtiger_Request $request): bool
	{
		if ($request->getModule() !== 'ProjektyRekrutacyjne' || $request->get('view') !== 'Detail') {
			return false;
		}
		if ($request->get('mode') !== 'showDetailViewByMode') {
			return false;
		}
		if ($request->get('requestMode') !== 'summary') {
			return false;
		}

		return true;
	}

	/**
	 * Related-list mass actions reuse ListView selection helpers (select-all, selectedIds).
	 *
	 * @param \App\Http\Vtiger_Request $request
	 * @return array
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);

		$jsScriptInstances = $this->checkAndConvertJsScripts([
			'modules.Base.resources.ListView',
			'modules.ProjektyRekrutacyjne.resources.KanbanCvSkillsQueryStorage',
		]);

		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
