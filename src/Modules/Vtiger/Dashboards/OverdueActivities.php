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

Class Vtiger_OverdueActivities_Dashboard extends Vtiger_IndexAjax_View
{

	/**
	 * Process
	 * @param Vtiger_Request $request
	 */
	public function process(Vtiger_Request $request)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();

		$moduleName = $request->getModule();
		$page = $request->get('page');
		$linkId = $request->get('linkid');
		$sortOrder = $request->get('sortorder');
		$orderBy = $request->get('orderby');
		$data = $request->getAll();

		$widget = \FreeCRM\Modules\Vtiger\Models\Widget::getInstance($linkId, $currentUser->getId());
		$owner = \Settings_WidgetsManagement_Module_Model::getDefaultUserId($widget, 'Calendar', $request->get('owner'));

		$pagingModel = new \FreeCRM\Modules\Vtiger\Models\Paging();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', (int) $widget->get('limit'));
		$pagingModel->set('orderby', $orderBy);
		$pagingModel->set('sortorder', $sortOrder);

		$overdueActivityLabels['status'] = \FreeCRM\Modules\Calendar\Models\Module::getComponentActivityStateLabel('overdue');
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$overDueActivities = ($owner === false) ? [] : $moduleModel->getCalendarActivities('overdue', $pagingModel, $owner, false, $overdueActivityLabels);

		$colorList = [];
		foreach ($overDueActivities as $activityModel) {
			$colorList[$activityModel->getId()] = \Settings_DataAccess_Module_Model::executeColorListHandlers('Calendar', $activityModel->getId(), $activityModel);
		}
		$viewer = $this->getViewer($request);

		$viewer->assign('SOURCE_MODULE', 'Calendar');
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('ACTIVITIES', $overDueActivities);
		$viewer->assign('COLOR_LIST', $colorList);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('NAMELENGTH', \FreeCRM\AppConfig::main('title_max_length'));
		$viewer->assign('HREFNAMELENGTH', \FreeCRM\AppConfig::main('href_max_length'));
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
