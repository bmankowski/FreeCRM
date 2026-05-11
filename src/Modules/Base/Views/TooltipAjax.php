<?php

namespace App\Modules\Base\Views;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */


use App\Http\Vtiger_Request;
class TooltipAjax  extends \App\Modules\Base\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$this->initializeListViewContents($request, $viewer);

		echo $viewer->view('TooltipContents.tpl', $moduleName, true);
	}

	public function initializeListViewContents(\App\Http\Vtiger_Request $request, \App\Runtime\CRM_Viewer $viewer)
	{
		$moduleName = $this->getModule($request);

		$recordId = $request->get('record');
		$tooltipViewModel = \App\Modules\Base\Models\TooltipView::getInstance($moduleName, $recordId);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('MODULE_MODEL', $tooltipViewModel->getRecord()->getModule());
		$viewer->assign('RECORD', $tooltipViewModel->getRecord());
		$viewer->assign('RECORD_STRUCTURE', $tooltipViewModel->getStructure());
	}
}
