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

use App\Modules\Settings\Groups\Models\Record as Settings_Groups_Record_Model;
Class Settings_Groups_Detail_View extends \App\Modules\Settings\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{

		$groupId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = Settings_Groups_Record_Model::getInstance($groupId);

		$viewer = $this->getViewer($request);

		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('MODULE', $qualifiedModuleName);
		$viewer->assign('USER_MODEL', \App\Modules\Users\Models\Record::getCurrentUserModel());

		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}
}
