<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */



namespace App\Modules\Reservations\Views;

class Calendar  extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());

		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Assign Reservations Calendar-specific data
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUserModel = $request->getUser();
		
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('CURRENT_VIEW', $request->get('view'));
		$viewer->assign('VIEW', $request->get('view'));
		$viewer->assign('CURRENT_USER', $currentUserModel);
		$viewer->assign('EVENT_LIMIT', \App\Core\AppConfig::module('Calendar', 'EVENT_LIMIT'));
		$viewer->assign('WEEK_VIEW', \App\Core\AppConfig::module('Calendar', 'SHOW_TIMELINE_WEEK') ? 'agendaWeek' : 'basicWeek');
		$viewer->assign('DAY_VIEW', \App\Core\AppConfig::module('Calendar', 'SHOW_TIMELINE_DAY') ? 'agendaDay' : 'basicDay');

		// Reservations Calendar needs QUICK_LINKS for sidebar navigation
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$linkModels = $moduleModel->getSideBarLinks($linkParams, $request->getUser());
		$activeLinkLabel = $this->processSidebarLinks($linkModels, $request);
		
		$viewer->assign('QUICK_LINKS', $linkModels);
		$viewer->assign('ACTIVE_SIDEBAR_LINK', $activeLinkLabel);
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		// Data already assigned in preProcess, just render
		$viewer = $this->getViewer($request);
		$viewer->view('CalendarView.tpl', $request->getModule());
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$moduleName = $request->getModule();
		$jsFileNames = array(
			'~libraries/fullcalendar/moment.min.js',
			'~libraries/fullcalendar/fullcalendar.js',
			'modules.' . $moduleName . '.resources.Calendar',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(\App\Http\Vtiger_Request $request)
	{
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~libraries/fullcalendar/fullcalendar.min.css',
			'~libraries/fullcalendar/fullcalendarCRM.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);

		return $headerCssInstances;
	}
}
