<?php

namespace App\Modules\Services\Views;

use App\Http\Vtiger_Request;

class PriceBookProductPopupAjax extends PriceBookProductPopup
{
	public function preProcess(Vtiger_Request $request, $display = true)
	{
		return true;
	}

	public function postProcess(Vtiger_Request $request)
	{
		return true;
	}

	public function process(Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$this->initializeListViewContents($request, $viewer);

		$viewer->assign('MODULE_NAME', $moduleName);
		$viewer->assign('USER_MODEL', $request->getUser());

		echo $viewer->view('PriceBookProductPopupContents.tpl', 'Products', true);
	}
}
