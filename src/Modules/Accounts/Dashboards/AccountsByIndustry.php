<?php

namespace App\Modules\Accounts\Dashboards;

/**
 * Widget show accounts by industry
 * @package YetiForce.Dashboard
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
use App\Http\Vtiger_Request;

class AccountsByIndustry  extends \App\Modules\Base\Views\Index
{

	/**
	 * Function to get params to searching in listview
	 * @param string $industry
	 * @param int $assignedto
	 * @param array $dates
	 * @return string
	 */
	public function getSearchParams($industry, $assignedto, $dates)
	{
		$listSearchParams = [];
		$conditions = array(array('industry', 'e', $industry));
		if ($assignedto != '')
			array_push($conditions, array('assigned_user_id', 'e', $assignedto));
		if (!empty($dates)) {
			array_push($conditions, array('createdtime', 'bw', $dates['start'] . ',' . $dates['end']));
		}
		$listSearchParams[] = $conditions;
		return '&search_params=' . \App\Utils\Json::encode($listSearchParams);
	}

	/**
	 * Function to get data to display chart
	 * @param int $owner
	 * @param array $dateFilter
	 * @return array
	 */
	public function getAccountsByIndustry($owner, $dateFilter)
	{
		$module = 'Accounts';

		$query = new \App\Db\Query();
		$query->select([
				'count' => new \yii\db\Expression('COUNT(*)'),
				'industryvalue' => new \yii\db\Expression("CASE WHEN vtiger_account.industry IS NULL OR vtiger_account.industry = '' THEN '' 
						ELSE vtiger_account.industry END")])
			->from('vtiger_account')
			->innerJoin('vtiger_crmentity', 'vtiger_account.accountid = vtiger_crmentity.crmid')
			->innerJoin('vtiger_industry', 'vtiger_account.industry = vtiger_industry.industry')
			->where(['deleted' => 0]);
		if (!empty($owner)) {
			$query->andWhere(['smownerid' => $owner]);
		}
		if (!empty($dateFilter)) {
			$query->andWhere(['between', 'createdtime', $dateFilter['start'] . ' 00:00:00', $dateFilter['end'] . ' 23:59:59']);
		}
		\App\Security\PrivilegeQuery::getConditions($query, $module);
		$query->groupBy(['vtiger_industry.sortorderid', 'industryvalue'])->orderBy('vtiger_industry.sortorderid');
		$dataReader = $query->createCommand()->query();
		$response = [];
		$i = 0;
		while ($row = $dataReader->read()) {
			$data[$i]['label'] = \App\Runtime\Vtiger_Language_Handler::translate($row['industryvalue'], 'Leads');
			$ticks[$i][0] = $i;
			$ticks[$i][1] = \App\Runtime\Vtiger_Language_Handler::translate($row['industryvalue'], 'Leads');
			$data[$i]['data'][0][0] = $i;
			$data[$i]['data'][0][1] = $row['count'];
			$name[] = $row['industryvalue'];
			$i++;
		}
		$response['chart'] = $data;
		$response['ticks'] = $ticks;
		$response['name'] = $name;

		return $response;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$linkId = $request->get('linkid');
		$data = $request->get('data');

		$widget = \App\Modules\Base\Models\Widget::getInstance($linkId, $currentUser->getId());
		if (!$request->has('owner'))
			$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget, 'Accounts');
		else
			$owner = $request->get('owner');
		$ownerForwarded = $owner;
		if ($owner == 'all')
			$owner = '';

		$createdTime = $request->get('createdtime');

		//Date conversion from user to database format
		$dates = [];
		if (!empty($createdTime)) {
			$dates['start'] = \App\Modules\Base\UiTypes\Date::getDBInsertedValue($createdTime['start']);
			$dates['end'] = \App\Modules\Base\UiTypes\Date::getDBInsertedValue($createdTime['end']);
		} else {
			$time = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultDate($widget);
			if ($time !== false) {
				$dates = $time;
			}
		}
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$data = $this->getAccountsByIndustry($owner, $dates);
		$listViewUrl = $moduleModel->getListViewUrl();
		$leadSIndustryAmount = count($data['name']);
		for ($i = 0; $i < $leadSIndustryAmount; $i++) {
			$data['links'][$i][0] = $i;
			$data['links'][$i][1] = $listViewUrl . $this->getSearchParams($data['name'][$i], $owner, $dates);
		}
		//Include special script and css needed for this widget

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('DTIME', $dates);

		$accessibleUsers = \App\Fields\Owner::getInstance('Accounts', $currentUser)->getAccessibleUsersForModule();
		$accessibleGroups = \App\Fields\Owner::getInstance('Accounts', $currentUser)->getAccessibleGroupForModule();
		$viewer->assign('ACCESSIBLE_USERS', $accessibleUsers);
		$viewer->assign('ACCESSIBLE_GROUPS', $accessibleGroups);
		$viewer->assign('OWNER', $ownerForwarded);

		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/AccountsByIndustry.tpl', $moduleName);
		}
	}
}
