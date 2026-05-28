<?php

namespace App\Modules\Settings\Groups\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteAjax extends \App\Modules\Settings\Base\Views\Index
{

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		return;
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		return;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');

		$recordModel = \App\Modules\Settings\Groups\Models\Record::getInstance($recordId);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('RECORD_MODEL', $recordModel);

		$viewer->assign('ALL_USERS', \App\Modules\Users\Models\Record::getAll());
		$viewer->assign('ALL_GROUPS', \App\Modules\Settings\Groups\Models\Record::getAll());

		echo $viewer->view('DeleteTransferForm.tpl', $qualifiedModuleName, true);
	}
}
