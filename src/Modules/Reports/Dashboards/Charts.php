<?php

namespace App\Modules\Reports\Dashboards;

/**
 * Wdiget to show chart from reports
 * @package YetiForce.Dashboard
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
use App\Http\Vtiger_Request;

class Charts extends \Vtiger_Index_View
{

	public function process(Vtiger_Request $request)
	{
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$moduleName = $request->getModule();
		$linkId = $request->get('linkid');
		$widgetId = $request->get('widgetid');
		$widget = \App\Modules\Vtiger\Models\Widget::getInstanceWithWidgetId($widgetId, $currentUser->getId());

		$data = [];
		$typeChart = '';
		$reportId = json_decode($widget->get('data'), true);
		$reportId = $reportId['reportId'];
		if (!empty($reportId)) {
			$reportModel = \App\Modules\Reports\Models\Record::getInstanceById($reportId);
			$reportChartModel = Reports_Chart_Model::getInstanceById($reportModel);
			$typeChart = $reportChartModel->getChartType();
			$data = $reportChartModel->getData();
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('CURRENTUSER', $currentUser);
		$viewer->assign('CHART_TYPE', $typeChart);
		$viewer->assign('SCRIPTS', $this->getScripts($typeChart));
		$viewer->assign('DATA', $data);
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/ChartsContents.tpl', $moduleName);
		} else {
			$viewer->view('dashboards/Charts.tpl', $moduleName);
		}
	}

	public function getScripts($chartType)
	{
		$jsFileNames = array(
			'modules.Reports.resources.TypeCharts',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		return $jsScriptInstances;
	}
}
