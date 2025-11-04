<?php

namespace App\Modules\Settings\PBXManager\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Index extends \App\Modules\Settings\Base\Views\Index
{

	public function __construct()
	{
		$this->exposeMethod('gatewayInfo');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$this->gatewayInfo($request);
	}

	public function gatewayInfo(\App\Http\Vtiger_Request $request)
	{
		$recordModel = \App\Modules\Settings\PBXManager\Models\Record::getInstance();
		$moduleModel = \App\Modules\Settings\PBXManager\Models\Module::getCleanInstance();
		$viewer = $this->getViewer($request);

		$viewer->assign('RECORD_ID', $recordModel->get('id'));
		$viewer->assign('MODULE_MODEL', $moduleModel);
		$viewer->assign('MODULE', $request->getModule(false));
		$viewer->assign('QUALIFIED_MODULE', $request->getModule(false));
		$viewer->assign('RECORD_MODEL', $recordModel);
		
		if ($request->isAjax()) {
			// AJAX request - return content only
			$viewer->view('IndexContent.tpl', $request->getModule(false));
		} else {
			// Initial page load - return full page with MainLayout
			$viewer->view('index.tpl', $request->getModule(false));
		}
	}
}
