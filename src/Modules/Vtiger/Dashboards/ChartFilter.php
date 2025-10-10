<?php

namespace FreeCRM\Modules\Vtiger\Dashboards;

/**
 * Widget as a chart with a filter
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
use FreeCRM\Http\Vtiger_Request;

class ChartFilter extends \Vtiger_Index_View
{

	public function process(Vtiger_Request $request, $widget = NULL)
	{
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		// Initialize Widget to the right-state of information
		if ($widget && !$request->has('widgetid')) {
			$widgetId = $widget->get('id');
		} else {
			$widgetId = $request->get('widgetid');
		}

		$widget = \FreeCRM\Modules\Vtiger\Models\Widget::getInstanceWithWidgetId($widgetId, $currentUser->getId());
		$chartFilterWidgetModel = Vtiger_ChartFilter_Model::getInstance();
		$chartFilterWidgetModel->setWidgetModel($widget);
		$data = $chartFilterWidgetModel->getChartData();
		$viewer->assign('WIDGET', $widget);
		$viewer->assign('CHART_MODEL', $chartFilterWidgetModel);
		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('BASE_MODULE', $chartFilterWidgetModel->getTargetModule());
		$viewer->assign('DATA_CHART', $data);
		$viewer->assign('CHART_TYPE', $chartFilterWidgetModel->getType());
		$viewer->assign('COLOR', $chartFilterWidgetModel->isColor());
		$content = $request->get('content');
		if (!empty($content)) {
			$viewer->view('dashboards/ChartFilterContents.tpl', $moduleName);
			$viewer->view('dashboards/ChartFilterFooter.tpl', $moduleName);
		} else {
			$widget->set('title', $chartFilterWidgetModel->getTitle());
			$viewer->view('dashboards/ChartFilterHeader.tpl', $moduleName);
		}
	}
}
