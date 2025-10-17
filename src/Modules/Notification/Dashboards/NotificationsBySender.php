<?php

namespace App\Modules\Notification\Dashboards;
use App\Modules\Settings\WidgetsManagement\Models\Module as Settings_WidgetsManagement_Module_Model;

/**
 * Notifications Dashboard Class
 * @package YetiForce.Dashboard
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
use App\Http\Vtiger_Request;

class NotificationsBySender extends \Vtiger_Index_View
{

	/**
	 * Return search params (use to in building address URL to listview)
	 * @param string $owner Name of user
	 * @param array $time
	 * @return string
	 */
	public function getSearchParams($owner, $time)
	{
		$listSearchParams = [];
		$conditions = [];
		if (!empty($time)) {
			$conditions [] = ['createdtime', 'bw', implode(',', $time)];
		}
		if (!empty($owner)) {
			$conditions [] = ['smcreatorid', 'e', $owner];
		}
		$listSearchParams[] = $conditions;
		return '&viewname=All&search_params=' . json_encode($listSearchParams);
	}

	/**
	 * Function to get data for chart Return number notification by sender
	 * @param array $time Contains start and end created time of natification
	 * @return array
	 */
	private function getNotificationBySender($time)
	{
		$accessibleUsers = \App\Fields\Owner::getInstance()->getAccessibleUsers();
		$moduleName = 'Notification';
		$listView = \App\Modules\Vtiger\Models\Module::getInstance($moduleName)->getListViewUrl();

		$time['start'] = \App\Fields\DateTimeField::convertToDBFormat($time['start']);
		$time['end'] = \App\Fields\DateTimeField::convertToDBFormat($time['end']);

		$query = new \App\Db\Query();
		$query->select(['count' => new \yii\db\Expression('COUNT(*)'), 'smcreatorid'])
			->from('vtiger_crmentity')
			->where([
				'and',
				['setype' => $moduleName],
				['deleted' => 0],
				['smcreatorid' => array_keys($accessibleUsers)],
				['>=', 'createdtime', $time['start'] . ' 00:00:00'],
				['<=', 'createdtime', $time['end'] . ' 23:59:59']
		]);
		\App\PrivilegeQuery::getConditions($query, $moduleName);
		$query->groupBy(['smcreatorid']);
		$dataReader = $query->createCommand()->query();
		$data = [];
		while ($row = $dataReader->read()) {
			$data [] = [
				$row['count'],
				$accessibleUsers[$row['smcreatorid']],
				$listView . $this->getSearchParams($accessibleUsers[$row['smcreatorid']], $time)
			];
		}
		return $data;
	}

	public function process(Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$widget = \App\Modules\Vtiger\Models\Widget::getInstance($request->get('linkid'), \App\Modules\Users\Models\Record::getCurrentUserModel()->getId());
		$time = $request->get('time');
		if (empty($time)) {
			$time = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDate($widget);
			if ($time === false) {
				$time['start'] = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
				$time['end'] = date('Y-m-d', mktime(23, 59, 59, date('m') + 1, 0, date('Y')));
			}
			$time['start'] = \App\Fields\DateTime::currentUserDisplayDate($time['start']);
			$time['end'] = \App\Fields\DateTime::currentUserDisplayDate($time['end']);
		}
		$data = $this->getNotificationBySender($time);
		$viewer->assign('DATA', $data);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DTIME', $time);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/NotificationsBySenderRecipient.tpl', $moduleName);
		}
	}
}
