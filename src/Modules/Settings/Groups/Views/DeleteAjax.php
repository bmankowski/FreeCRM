<?php

namespace FreeCRM\Modules\Settings\Groups\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

use FreeCRM\Modules\Settings\Groups\Models\Record as Settings_Groups_Record_Model;
class DeleteAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function preProcess(\FreeCRM\Http\Vtiger_Request $request, $display = true)
	{
		return;
	}

	public function postProcess(\FreeCRM\Http\Vtiger_Request $request)
	{
		return;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');

		$recordModel = Settings_Groups_Record_Model::getInstance($recordId);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('RECORD_MODEL', $recordModel);

		$viewer->assign('ALL_USERS', \FreeCRM\Modules\Users\Models\Record::getAll());
		$viewer->assign('ALL_GROUPS', Settings_Groups_Record_Model::getAll());

		echo $viewer->view('DeleteTransferForm.tpl', $qualifiedModuleName, true);
	}
}
