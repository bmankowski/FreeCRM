<?php

namespace App\Modules\Settings\Roles\Views;
use App\Modules\Settings\RolesViews\IndexAjax;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class EditAjax extends \App\Modules\Settings\Roles\Views\IndexAjax
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$record = $request->get('record');
		$parentRoleId = $request->get('parent_roleid');

		if (!empty($record)) {
			$recordModel = \App\Modules\Settings\Roles\Models\Record::getInstanceById($record);
			$viewer->assign('MODE', 'edit');
		} else {
			$recordModel = new \App\Modules\Settings\Roles\Models\Record();
			$recordModel->setParent(\App\Modules\Settings\Roles\Models\Record::getInstanceById($parentRoleId));
			$viewer->assign('MODE', '');
		}

		$viewer->assign('ALL_PROFILES', \App\Modules\Settings\Profiles\Models\Record::getAll());
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('RECORD_ID', $record);
		$viewer->assign('QUALIFIED_MODULE', $qualifiedModuleName);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('USER_MODEL', $request->getUser());

		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}
}
