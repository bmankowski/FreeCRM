<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class Vtiger_OverdueActivities_Dashboard extends \App\Modules\Vtiger\Views\IndexAjax
{

	/**
	 * Process
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();

		$moduleName = $request->getModule();
		$page = $request->get('page');
		$linkId = $request->get('linkid');
		$sortOrder = $request->get('sortorder');
		$orderBy = $request->get('orderby');
		$data = $request->getAll();

		$widget = \App\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget, 'Calendar', $request->get('owner'));

		$pagingModel = new \App\Modules\Vtiger\Models\Paging();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', (int) $widget->get('limit'));
		$pagingModel->set('orderby', $orderBy);
		$pagingModel->set('sortorder', $sortOrder);

		$overdueActivityLabels['status'] = \App\Modules\Calendar\Models\Module::getComponentActivityStateLabel('overdue');
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$overDueActivities = ($owner === false) ? [] : $moduleModel->getCalendarActivities('overdue', $pagingModel, $owner, false, $overdueActivityLabels);

		$colorList = [];
		foreach ($overDueActivities as $activityModel) {
			$colorList[$activityModel->getId()] = \App\Modules\Settings\DataAccess\Models\Module::executeColorListHandlers('Calendar', $activityModel->getId(), $activityModel);
		}
		$viewer = $this->getViewer($request);

		$viewer->assign('SOURCE_MODULE', 'Calendar');
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('ACTIVITIES', $overDueActivities);
		$viewer->assign('COLOR_LIST', $colorList);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('NAMELENGTH', \App\AppConfig::main('title_max_length'));
		$viewer->assign('HREFNAMELENGTH', \App\AppConfig::main('href_max_length'));
		$viewer->assign('NODATAMSGLABLE', 'LBL_NO_OVERDUE_ACTIVITIES');
		$viewer->assign('OWNER', $owner);
		$viewer->assign('LISTVIEWLINKS', true);
		$viewer->assign('DATA', $data);
		$viewer->assign('USER_CONDITIONS', ['condition' => ['vtiger_activity.status' => $overdueActivityLabels]]);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/CalendarActivitiesContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/CalendarActivities.tpl', $moduleName);
		}
	}
}
