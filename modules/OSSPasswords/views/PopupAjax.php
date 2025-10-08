<?php

/**
 * PopupAjax View Class
 * @package YetiForce.View
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Http\Vtiger_Request;
class OSSPasswords_PopupAjax_View extends OSSPasswords_Popup_View
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getListViewCount');
		$this->exposeMethod('getRecordsCount');
		$this->exposeMethod('getPageCount');
	}

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
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
