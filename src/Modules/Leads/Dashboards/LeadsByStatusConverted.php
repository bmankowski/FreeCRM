<?php

namespace App\Modules\Leads\Dashboards;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

use App\Http\Vtiger_Request;

class LeadsByStatusConverted extends \Vtiger_Index_View
{

	public function getSearchParams($value, $assignedto, $dates)
	{
		$listSearchParams = array();
		$conditions = array(array('leadstatus', 'e', $value));
		if ($assignedto != '')
			array_push($conditions, array('assigned_user_id', 'e', $assignedto));
		if (!empty($dates)) {
			array_push($conditions, array('createdtime', 'bw', $dates['start'] . ' 00:00:00,' . $dates['end'] . ' 23:59:59'));
		}
		$listSearchParams[] = $conditions;
		return '&search_params=' . json_encode($listSearchParams);
	}

	public function process(Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$linkId = $request->get('linkid');
		$data = $request->get('data');
		$createdTime = $request->get('createdtime');

		$widget = \App\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		if (!$request->has('owner'))
			$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget, 'Leads');
		else
			$owner = $request->get('owner');
		$ownerForwarded = $owner;
		if ($owner == 'all')
			$owner = '';

		$dates = [];
		//Date conversion from user to database format
		if (!empty($createdTime)) {
			$dates['start'] = \App\Modules\Vtiger\UiTypes\Date::getDBInsertedValue($createdTime['start']);
			$dates['end'] = \App\Modules\Vtiger\UiTypes\Date::getDBInsertedValue($createdTime['end']);
		} else {
			$time = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDate($widget);
			if($time !== false){
				$dates = $time;
			}
		}

		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$data = ($owner === false) ? array() : $moduleModel->getLeadsByStatusConverted($owner, $dates);
		$listViewUrl = $moduleModel->getListViewUrl();
		foreach ($data as &$value) {
			$value['links'] = $listViewUrl . $this->getSearchParams($value[2], $owner, $dates);
		}
		//Include special script and css needed for this widget

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('DTIME', $dates);

		$accessibleUsers = \App\Fields\Owner::getInstance('Leads', $currentUser)->getAccessibleUsersForModule();
		$accessibleGroups = \App\Fields\Owner::getInstance('Leads', $currentUser)->getAccessibleGroupForModule();
		$viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
		$viewer->assign('ACCESSIBLE_GROUPS', $accessibleGroups);
		$viewer->assign('OWNER', $ownerForwarded);

		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/LeadsByStatusConverted.tpl', $moduleName);
		}
	}
}
