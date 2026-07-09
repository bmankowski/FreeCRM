<?php

namespace App\Modules\PriceBooks\Views;

use App\Http\Vtiger_Request;

class ProductPriceBookPopupAjax extends ProductPriceBookPopup
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

		echo $viewer->view('ProductPriceBookPopupContents.tpl', 'PriceBooks', true);
	}
}
