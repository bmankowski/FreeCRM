<?php

namespace App\Modules\SSalesProcesses\Dashboards;

/**
 * Widget show estimated value by status
 * @package YetiForce.Dashboard
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class EstimatedValueByStatus  extends \App\Modules\Base\Views\Index
{

	/**
	 * Function to get search params in address listview
	 * @param int $owner number id of user
	 * @param string $status
	 * @return string
	 */
	public function getSearchParams($owner, $status)
	{
		$listSearchParams = [];
		$conditions = [];
		if (!empty($owner)) {
			$conditions [] = ['assigned_user_id', 'e', $owner];
		}
		if (!empty($status)) {
			$conditions [] = ['ssalesprocesses_status', 'e', $status];
		}
		$listSearchParams[] = $conditions;
		return '&viewname=All&search_params=' . json_encode($listSearchParams);
	}

	/**
	 * Function to get data to chart
	 * @param int $owner
	 * @return array
	 */
	private function getEstimatedValue($owner = false)
	{
		$moduleName = 'SSalesProcesses';
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$query = (new \App\Db\Query())->select('SUM(u_#__ssalesprocesses.estimated) AS estimated, u_#__ssalesprocesses.ssalesprocesses_status')
			->from('u_yf_ssalesprocesses')
			->innerJoin('vtiger_crmentity', 'u_#__ssalesprocesses.ssalesprocessesid = vtiger_crmentity.crmid')
			->where(['and', ['<>', 'ssalesprocesses_status', ''], ['vtiger_crmentity.deleted' => 0], ['not', ['ssalesprocesses_status' => null]]]);
		\App\Security\PrivilegeQuery::getConditions($query, $moduleName);
		if (!empty($owner)) {
			$query->andWhere(['vtiger_crmentity.smownerid' => $owner]);
		}
		$query->groupBy('u_#__ssalesprocesses.ssalesprocesses_status');
		$dataReader = $query->createCommand()->query();
		$data = [];
		$i = 1;
		$currencyInfo = \vtlib\Functions:: getDefaultCurrencyInfo();
		while ($row = $dataReader->read()) {
			$data [] = [
				\App\Runtime\Vtiger_Language_Handler::translate($row['ssalesprocesses_status'], $moduleName) . ' - ' . \App\Fields\CurrencyField::convertToUserFormat($row['estimated']) . ' ' . $currencyInfo['currency_symbol'],
				$i++,
				$moduleModel->getListViewUrl() . $this->getSearchParams($owner, $row['ssalesprocesses_status'])
			];
		}
		return $data;
	}

	/**
	 * Main function
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function process(\App\Http\Vtiger_Request $request)
	{
		$currentUser = $request->getUser();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$linkId = $request->get('linkid');
		$data = $request->get('data');
		$widget = \App\Modules\Base\Models\Widget::getInstance($linkId, $currentUser->getId());
		if (!$request->has('owner'))
			$owner = \App\Modules\Settings\WidgetsManagement\Models\Module::getDefaultUserId($widget, $moduleName);
		else
			$owner = $request->get('owner');
		if ($owner == 'all')
			$owner = '';


		$data = $this->getEstimatedValue($owner);

		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('DATA', $data);
		$viewer->assign('CURRENTUSER', $currentUser);

		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/DashBoardWidgetContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/EstimatedValueByStatus.tpl', $moduleName);
		}
	}
}
