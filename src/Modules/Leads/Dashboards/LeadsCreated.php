<?php

namespace App\Modules\Leads\Dashboards;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use App\Http\Vtiger_Request;

class LeadsCreated  extends \App\Modules\Vtiger\Views\Index
{

	/**
	 * Function to get the list of Script models to be included
	 * @param \App\Http\Vtiger_Request $request
	 * @return <Array> - List of \App\Modules\Vtiger\Models\JsScript instances
	 */
	public function getFooterScripts(\App\Http\Vtiger_Request $request)
	{

		$jsFileNames = array(
//			'~libraries/jquery/jqplot/plugins/jqplot.cursor.min.js',
//			'~libraries/jquery/jqplot/plugins/jqplot.dateAxisRenderer.min.js',
//			'~libraries/jquery/jqplot/plugins/jqplot.logAxisRenderer.min.js',
//			'~libraries/jquery/jqplot/plugins/jqplot.canvasTextRenderer.min.js',
//			'~libraries/jquery/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js'
		);

		$headerScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $headerScriptInstances;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$linkId = $request->get('linkid');
		$createdTime = $request->get('createdtime');
		$owner = $request->get('owner');

		//Date conversion from user to database format
		if (!empty($createdTime)) {
			$dates['start'] = \App\Modules\Vtiger\UiTypes\Date::getDBInsertedValue($createdTime['start']);
			$dates['end'] = \App\Modules\Vtiger\UiTypes\Date::getDBInsertedValue($createdTime['end']);
		}

		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$data = $moduleModel->getLeadsCreated($owner, $dates);

		$widget = \App\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());

		//Include special script and css needed for this widget
		$viewer->assign('SCRIPTS', $this->getHeaderScripts($request));

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('CURRENTUSER', $currentUser);

		$accessibleUsers = \App\Fields\Owner::getInstance('Leads', $currentUser)->getAccessibleUsersForModule();
		$viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/LeadsCreated.tpl', $moduleName);
		}
	}
}
