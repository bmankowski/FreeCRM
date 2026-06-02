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

class Workflows extends \App\Modules\Settings\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		$qualifiedModule = $request->getModule(false);
		$statusOptions = \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransition::getStatusOptions();
		$transitionWorkflowMap = \App\Modules\Settings\Workflows\Models\RelationTrigger::getWorkflowsForTransitionMatrix();
		$createTransitionWorkflowUrls = [];
		foreach ($statusOptions as $fromCode => $fromLabel) {
			foreach ($statusOptions as $toCode => $toLabel) {
				if ($fromCode === $toCode) {
					continue;
				}
				$createTransitionWorkflowUrls[$fromCode][$toCode] = \App\Modules\Settings\Workflows\Models\RelationTrigger::buildCreateWorkflowUrl(
					$fromCode,
					$toCode
				);
			}
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->assign('STATUS_OPTIONS', $statusOptions);
		$viewer->assign('TRANSITION_WORKFLOW_MAP', $transitionWorkflowMap);
		$viewer->assign('CREATE_WORKFLOW_URL', \App\Modules\Settings\Workflows\Models\RelationTrigger::buildCreateWorkflowUrl());
		$viewer->assign('CREATE_TRANSITION_WORKFLOW_URLS', $createTransitionWorkflowUrls);
		$viewer->assign('VIEW_ALL_WORKFLOWS_URL', 'index.php?module=Workflows&parent=Settings&view=ListView&sourceModule=ProjektyRekrutacyjne');

		if ($request->isAjax()) {
			$viewer->view('WorkflowsContent.tpl', $qualifiedModule);
		} else {
			$viewer->view('Workflows.tpl', $qualifiedModule);
		}
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request): array
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			"modules.Settings.$moduleName.resources.Workflows",
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);

		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
