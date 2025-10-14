<?php

namespace FreeCRM\Modules\Settings\Profiles\Views;
use FreeCRM\Modules\Settings\ProfilesModels\Record as Settings_Profiles_Record_Model;
use FreeCRM\Modules\Settings\ProfilesViews\IndexAjax;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteAjax extends \FreeCRM\Modules\Settings\Profiles\Views\IndexAjax
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$recordId = $request->get('record');

		$recordModel = Settings_Profiles_Record_Model::getInstanceById($recordId);

		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('ALL_RECORDS', Settings_Profiles_Record_Model::getAll());
		$viewer->assign('RECORD_MODEL', $recordModel);

		echo $viewer->view('DeleteTransferForm.tpl', $qualifiedModuleName, true);
	}
}
