<?php

namespace App\Modules\Documents\Views;

use App\Http\Vtiger_Request;

class PopupAjax extends Popup
{
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getListViewCount');
		$this->exposeMethod('getRecordsCount');
		$this->exposeMethod('getPageCount');
	}

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
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$this->initializeListViewContents($request, $viewer);
		echo $viewer->view('PopupContents.tpl', $moduleName, true);
	}
}
