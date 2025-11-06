<?php

namespace App\Modules\Calendar\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */


use App\Http\Vtiger_Request;
class Calendar  extends \App\Modules\Base\Views\Index
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$userPrivilegesModel = \App\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleName);

		if (!$permission) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Assign Calendar-specific data
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$currentUserModel = $request->getUser();
		
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('CURRENT_VIEW', $request->get('view'));
		$viewer->assign('CURRENT_USER', $currentUserModel);
		$viewer->assign('EVENT_LIMIT', \App\AppConfig::module('Calendar', 'EVENT_LIMIT'));
		$viewer->assign('WEEK_VIEW', \App\AppConfig::module('Calendar', 'SHOW_TIMELINE_WEEK') ? 'agendaWeek' : 'basicWeek');
		$viewer->assign('DAY_VIEW', \App\AppConfig::module('Calendar', 'SHOW_TIMELINE_DAY') ? 'agendaDay' : 'basicDay');
		$viewer->assign('ACTIVITY_STATE_LABELS', \App\Json::encode([
				'current' => \App\Modules\Calendar\Models\Module::getComponentActivityStateLabel('current'),
				'history' => \App\Modules\Calendar\Models\Module::getComponentActivityStateLabel('history')
		]));
	}

	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{
		$headerScriptInstances = parent::getFooterScripts($request);
		$jsFileNames = array(
			'~libraries/fullcalendar/moment.min.js',
			'~libraries/fullcalendar/fullcalendar.js',
			'modules.Calendar.resources.CalendarView',
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

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		// Data already assigned in preProcess, just render
		$viewer->view('CalendarView.tpl', $moduleName);
	}
}
