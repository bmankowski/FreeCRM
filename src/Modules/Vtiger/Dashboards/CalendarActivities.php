<?php

namespace App\Modules\Vtiger\Dashboards;

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

class CalendarActivities  extends \App\Modules\Vtiger\Views\Index
{

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$data = $request->getAll();

		$stateActivityLabels = \App\Modules\Calendar\Models\Module::getComponentActivityStateLabel();

		$page = $request->get('page');
		$linkId = $request->get('linkid');
		$sortOrder = $request->get('sortorder');
		$orderBy = $request->get('orderby');

		$params = ['status' => [
				$stateActivityLabels['not_started'],
				$stateActivityLabels['in_realization']
			]
		];
		$conditions = [
			'condition' => [
				'vtiger_activity.status' => $params['status']
			]
		];
		$widget = \App\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget, 'Calendar', $request->get('owner'));

		$pagingModel = new \App\Modules\Vtiger\Models\Paging();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', (int) $widget->get('limit'));
		$pagingModel->set('orderby', $orderBy);
		$pagingModel->set('sortorder', $sortOrder);

		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$calendarActivities = ($owner === false) ? [] : $moduleModel->getCalendarActivities('upcoming', $pagingModel, $owner, false, $params);

		$colorList = [];
		foreach ($calendarActivities as $activityModel) {
			$colorList[$activityModel->getId()] = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers('Calendar', $activityModel->getId(), $activityModel);
		}
		$msgLabel = 'LBL_NO_SCHEDULED_ACTIVITIES';
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('SOURCE_MODULE', 'Calendar');
		$viewer->assign('COLOR_LIST', $colorList);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('ACTIVITIES', $calendarActivities);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('CURRENTUSER', $currentUser);
		$title_max_length = vglobal('title_max_length');
		$href_max_length = vglobal('href_max_length');
		$viewer->assign('NAMELENGTH', $title_max_length);
		$viewer->assign('OWNER', $owner);
		$viewer->assign('HREFNAMELENGTH', $href_max_length);
		$viewer->assign('NODATAMSGLABLE', $msgLabel);
		$viewer->assign('LISTVIEWLINKS', true);
		$viewer->assign('DATA', $data);
		$viewer->assign('USER_CONDITIONS', $conditions);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/CalendarActivitiesContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/CalendarActivities.tpl', $moduleName);
		}
	}
}
