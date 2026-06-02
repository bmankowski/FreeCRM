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

class TransitionMail extends \App\Modules\Settings\Base\Views\Index
{
	public function process(\App\Http\Vtiger_Request $request): void
	{
		$qualifiedModule = $request->getModule(false);
		$statusOptions = \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransitionMail::getStatusOptions();
		$matrix = \App\Modules\ProjektyRekrutacyjne\Services\RecruitmentStatusTransitionMail::getMatrixForDisplay();
		$templateOptions = \App\Email\Mail::getTempleteList('ProjektyRekrutacyjne');

		$viewer = $this->getViewer($request);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModule);
		$viewer->assign('STATUS_OPTIONS', $statusOptions);
		$viewer->assign('MAIL_MATRIX', $matrix);
		$viewer->assign('TEMPLATE_OPTIONS', $templateOptions);

		if ($request->isAjax()) {
			$viewer->view('TransitionMailContent.tpl', $qualifiedModule);
		} else {
			$viewer->view('TransitionMail.tpl', $qualifiedModule);
		}
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request): array
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = [
			"modules.Settings.$moduleName.resources.TransitionMail",
		];
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);

		return array_merge($headerScriptInstances, $jsScriptInstances);
	}
}
