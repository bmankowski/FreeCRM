<?php

namespace App\Modules\Reports\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */
/*
 * Class for reloading the Reports headers
 */


use App\Http\Vtiger_Request;
class ListAjax extends ListView
{
	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getListViewCount');
		$this->exposeMethod('getRecordsCount');
		$this->exposeMethod('getPageCount');
	}

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);

		$folders = $moduleModel->getFolders();
		$listViewModel = new \App\Modules\Reports\Models\ListView();
		$listViewModel->set('module', $moduleModel);

		$linkModels = $listViewModel->getListViewLinks(false);

		$viewer->assign('LISTVIEW_LINKS', $linkModels);
		$viewer->assign('FOLDERS', $folders);

		$viewer->view('ListViewFolders.tpl', $moduleName);
	}
}
