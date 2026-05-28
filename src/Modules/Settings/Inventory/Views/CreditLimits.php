<?php

namespace App\Modules\Settings\Inventory\Views;
use App\Http\Vtiger_Request;



/**
 * @package YetiForce.Views
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */
class CreditLimits extends \App\Modules\Settings\Base\Views\Index
{

	public function getView()
	{
		return 'CreditLimits';
	}

	public function process(\App\Http\Vtiger_Request $request)
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
		$currency = \App\Modules\Base\Helpers\Util::getBaseCurrency();
		$viewer->assign('CURRENCY', $currency);
		
		// Prepare Inventory IndexContent-specific data for IndexContent template
		$this->prepareInventoryIndexContentData($viewer, $currency);
		
		if ($request->isAjax()) {
			$viewer->view('IndexContent.tpl', $qualifiedModuleName);
		} else {
			$viewer->view('IndexView.tpl', $qualifiedModuleName);
		}
	}
	
	/**
	 * Prepare data for Inventory IndexContent template
	 * Moves function calls from template to controller for better MVC separation
	 */
	protected function prepareInventoryIndexContentData($viewer, $currency)
	{
		$viewer->assign('CURRENCY_JSON', \App\Utils\Json::encode($currency));
		// Set CURRENCY_BOOL based on view type - true for CreditLimits (currency), false for Taxes (percentage)
		$view = $this->getView();
		$viewer->assign('CURRENCY_BOOL', $view === 'CreditLimits');
	}

	public function getPageLabels(\App\Http\Vtiger_Request $request)
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
