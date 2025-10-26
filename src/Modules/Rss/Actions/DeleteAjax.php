<?php

namespace App\Modules\Rss\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteAjax extends \App\Runtime\BaseActionController
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$recordModel = \App\Modules\Rss\Models\Record::getInstanceById($recordId, $moduleName);
		$recordModel->delete();

		$response = new \App\Http\Vtiger_Response();
		$response->setResult(array('record' => $recordId, 'module' => $moduleName));
		$response->emit();
	}
}
