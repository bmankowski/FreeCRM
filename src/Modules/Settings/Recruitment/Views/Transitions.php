<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Settings\Recruitment\Views;

class Transitions extends \App\Modules\Settings\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		$qualifiedModule = $request->getModule(false);
		$statusOptions = \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::getStatusOptions();
		$matrix = \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::getMatrixForDisplay();
		$isConfigured = \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::isConfigured();
		$checkedTransitions = [];
		foreach ($matrix as $from => $targets) {
			foreach ($targets as $to) {
				$checkedTransitions[$from][$to] = true;
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->assign('STATUS_OPTIONS', $statusOptions);
		$viewer->assign('CHECKED_TRANSITIONS', $checkedTransitions);
		$viewer->assign('IS_CONFIGURED', $isConfigured);

		if ($request->isAjax()) {
			$viewer->view('TransitionsContent.tpl', $qualifiedModule);
		} else {
			$viewer->view('Transitions.tpl', $qualifiedModule);
		}
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request): array
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			"modules.Settings.$moduleName.resources.Transitions",
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);

		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
