<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */

class Vtiger_CreatedNotMineActivities_Dashboard extends \App\Modules\Vtiger\Views\IndexAjax
{

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
		if (!$request->has('owner'))
			$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget);
		else
			$owner = $request->get('owner');

		$pagingModel = new \App\Modules\Vtiger\Models\Paging();
		$pagingModel->set('page', $page);
		$pagingModel->set('limit', (int) $widget->get('limit'));
		$pagingModel->set('orderby', $orderBy);
		$pagingModel->set('sortorder', $sortOrder);

		$params = [];
		$params['status'] = \App\Modules\Calendar\Models\Module::getComponentActivityStateLabel('current');
		$params['user'] = $currentUser->getId();
		$conditions = [
			'condition' => [
				'and',
				['vtiger_activity.status' => $params['status']],
				['vtiger_crmentity.smcreatorid' => $params['user']],
				['not in', 'vtiger_crmentity.smownerid', $params['user']]
			]
		];

		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$overDueActivities = ($owner === false) ? [] : $moduleModel->getCalendarActivities('createdByMeButNotMine', $pagingModel, $owner, false, $params);

		$viewer = $this->getViewer($request);

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('SOURCE_MODULE', 'Calendar');
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('ACTIVITIES', $overDueActivities);
		$viewer->assign('PAGING_MODEL', $pagingModel);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('NAMELENGTH', \App\AppConfig::main('title_max_length'));
		$viewer->assign('HREFNAMELENGTH', \App\AppConfig::main('href_max_length'));
		$viewer->assign('NODATAMSGLABLE', 'LBL_NO_RECORDS_MATCHED_THIS_CRITERIA');
		$viewer->assign('OWNER', $owner);
		$viewer->assign('DATA', $data);
		$viewer->assign('USER_CONDITIONS', $conditions);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/CalendarActivitiesContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/CreatedNotMineActivities.tpl', $moduleName);
		}
	}
}
