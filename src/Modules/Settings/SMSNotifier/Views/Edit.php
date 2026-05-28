<?php

namespace App\Modules\Settings\SMSNotifier\Views;
use App\Modules\Settings\SMSNotifierModels\Record;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Edit extends \App\Modules\Settings\Base\Views\IndexAjax
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$recordId = $request->get('record');
		$qualifiedModuleName = $request->getModule(false);

		if ($recordId) {
			$recordModel = \App\Modules\Settings\SMSNotifier\Models\Record::getInstanceById($recordId, $qualifiedModuleName);
		} else {
			$recordModel = \App\Modules\Settings\SMSNotifier\Models\Record::getCleanInstance($qualifiedModuleName);
		}

		$viewer = $this->getViewer($request);
		$viewer->assign('RECORD_ID', $recordId);
		$viewer->assign('RECORD_MODEL', $recordModel);
		$viewer->assign('EDITABLE_FIELDS', $recordModel->getEditableFields());
		$viewer->assign('PROVIDERS_FIELD_MODELS', \App\Modules\Settings\SMSNotifier\Models\ProviderField::getAll());
		$viewer->assign('QUALIFIED_MODULE_NAME', $qualifiedModuleName);
		$viewer->assign('PROVIDERS', $recordModel->getModule()->getAllProviders());

		$viewer->view('EditView.tpl', $qualifiedModuleName);
	}
}
