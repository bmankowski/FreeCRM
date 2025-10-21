<?php

namespace App\Modules\Settings\Inventory\Views;
use App\Http\Vtiger_Request;



/**
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class CreditLimits extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function getView()
	{
		return 'CreditLimits';
	}

	public function process(Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		$view = $this->getView();
		$recordModel = new \App\Modules\Settings\Inventory\Models\Record();
		$recordModel->setType($view);
		$allData = \App\Modules\Settings\Inventory\Models\Record::getDataAll($view);

		$qualifiedModuleName = $request->getModule(false);
		$viewer = $this->getViewer($request);
		$viewer->assign('PAGE_LABELS', $this->getPageLabels($request));
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('INVENTORY_DATA', $allData);
		$viewer->assign('VIEW', $view);
		$viewer->assign('CURRENCY', \App\Modules\Vtiger\helpers\Util::getBaseCurrency());
		$viewer->view('Index.tpl', $qualifiedModuleName);
	}

	public function getPageLabels(Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		if ($request->get('type')) {
			$view = $request->get('type');
		} else {
			$view = $request->get('view');
		}
		$translations = [];
		$translations['title'] = 'LBL_' . strtoupper($view);
		$translations['title_single'] = 'LBL_' . strtoupper($view) . '_SINGLE';
		$translations['description'] = 'LBL_' . strtoupper($view) . '_DESCRIPTION';
		return $translations;
	}
}
