<?php

namespace App\Modules\OSSTimeControl\Views;

/* +***********************************************************************************************************************************
 * The contents of this file are subject to the YetiForce Public License Version 1.1 (the "License"); you may not use this file except
 * in compliance with the License.
 * Software distributed under the License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND, either express or implied.
 * See the License for the specific language governing rights and limitations under the License.
 * The Original Code is YetiForce.
 * The Initial Developer of the Original Code is YetiForce. Portions created by YetiForce are Copyright (C) www.yetiforce.com. 
 * All Rights Reserved.
 * *********************************************************************************************************************************** */


use App\Http\Vtiger_Request;
class Calendar  extends \App\Modules\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		parent::preProcess($request, false);
		
		// Assign OSSTimeControl Calendar-specific data
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

		// OSSTimeControl Calendar needs QUICK_LINKS for sidebar navigation
		$linkParams = array('MODULE' => $moduleName, 'ACTION' => $request->get('view'));
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$linkModels = $moduleModel->getSideBarLinks($linkParams);
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
