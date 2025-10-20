<?php

namespace App\Modules\Vtiger\Views;

/* * ************************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ************************************************************************************ */


use App\Http\Vtiger_Request;
class MergeRecord  extends \App\Modules\Vtiger\Views\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$records = $request->get('records');
		$records = explode(',', $records);
		$module = $request->getModule();
		$moduleModel = \App\Modules\Vtiger\Models\Module::getInstance($module);
		$fieldModels = $moduleModel->getFields();

		foreach ($records as $record) {
			$recordModels[] = \App\Modules\Vtiger\Models\Record::getInstanceById($record);
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('RECORDS', $records);
		$viewer->assign('RECORDMODELS', $recordModels);
		$viewer->assign('FIELDS', $fieldModels);
		$viewer->assign('MODULE', $module);
		$viewer->view('MergeRecords.tpl', $module);
	}
}
