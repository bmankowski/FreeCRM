<?php

declare(strict_types=1);

namespace App\Modules\ProjektyRekrutacyjne\Views;

use App\Http\Vtiger_Request;
use App\Modules\ProjektyRekrutacyjne\Services\RecruitmentProjectsDashboard;

class DashBoard extends \App\Modules\Base\Views\Index
{
	public function checkPermission(Vtiger_Request $request): void
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		if (!$userPrivilegesModel->hasModulePermission($request->getModule())) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(Vtiger_Request $request, $display = true): void
	{
		parent::preProcess($request, false);

		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);

		$linkParams = ['MODULE' => $moduleName, 'ACTION' => $request->get('view')];
		$linkModels = $moduleModel->getSideBarLinks($linkParams, $request->getUser());
		$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);

		$viewer->assign('QUICK_LINKS', $linkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MODULE_NAME', $moduleName);
	}

	public function process(Vtiger_Request $request): void
	{
		$viewer = $this->getViewer($request);
		$viewer->assign('DASHBOARD_ROWS', RecruitmentProjectsDashboard::getRows());
		$viewer->assign('DASHBOARD_STATUS_COLUMNS', RecruitmentProjectsDashboard::getStatusColumns());
		$viewer->assign('STATUS_TRANSITIONS_JSON', RecruitmentProjectsDashboard::getStatusTransitionsJson());
		$viewer->view('DashBoard.tpl', $request->getModule());
	}

	public function getPageTitle(Vtiger_Request $request): string
	{
		$moduleName = $request->getModule();

		return \App\Runtime\Vtiger_Language_Handler::translate($moduleName, $moduleName)
			. ' - '
			. \App\Runtime\Vtiger_Language_Handler::translate('LBL_RECRUITMENT_PROJECTS_DASHBOARD', $moduleName);
	}

	public function getHeaderCss(Vtiger_Request $request): array
	{
		$css = parent::getHeaderCss($request);
		$moduleCss = $this->checkAndConvertCssStyles([
			'modules.ProjektyRekrutacyjne.resources.RecruitmentProjectKanban',
			'modules.ProjektyRekrutacyjne.resources.RecruitmentProjectsDashboard',
		]);

		return array_merge($css, $moduleCss);
	}

	public function getFooterScripts(Vtiger_Request $request): array
	{
		return $this->stripCkEditorScripts(parent::getFooterScripts($request));
	}
}
