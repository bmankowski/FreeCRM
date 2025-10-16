<?php

namespace FreeCRM\Modules\Settings\Profiles\Views;
use FreeCRM\Modules\Settings\ProfilesModels\Record as Settings_Profiles_Record_Model;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

use FreeCRM\Modules\Vtiger\Models\Action as Vtiger_Action_Model;
class Detail extends \FreeCRM\Modules\Settings\Vtiger\Views\Index
{

	public function getBreadcrumbTitle(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if ($request->get('record')) {
			$recordModel = Settings_Profiles_Record_Model::getInstanceById($request->get('record'));
			$title = $recordModel->getName();
		} else {
			$title = vtranslate('LBL_VIEW_DETAIL', $moduleName);
		}
		return $title;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = Settings_Profiles_Record_Model::getInstanceById($recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('ALL_BASIC_ACTIONS', \Vtiger_Action_Model::getAllBasic(true));
		$viewer->assign('ALL_UTILITY_ACTIONS', \Vtiger_Action_Model::getAllUtility(true));
		$viewer->assign('USER_MODEL', \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel());

		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}
}
