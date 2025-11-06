<?php

namespace App\Modules\Settings\Profiles\Views;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Detail extends \App\Modules\Settings\Base\Views\Index
{

	public function getBreadcrumbTitle(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if ($request->get('record')) {
			$recordModel = \App\Modules\Settings\Profiles\Models\Record::getInstanceById($request->get('record'));
			$title = $recordModel->getName();
		} else {
			$title = \App\Runtime\Vtiger_Language_Handler::translate('LBL_VIEW_DETAIL', $moduleName);
		}
		return $title;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);

		$recordModel = \App\Modules\Settings\Profiles\Models\Record::getInstanceById($recordId);

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('ALL_BASIC_ACTIONS', \App\Modules\Base\Models\Action::getAllBasic(true));
		$viewer->assign('ALL_UTILITY_ACTIONS', \App\Modules\Base\Models\Action::getAllUtility(true));
		$viewer->assign('USER_MODEL', $request->getUser());

		$viewer->view('DetailView.tpl', $qualifiedModuleName);
	}
}
