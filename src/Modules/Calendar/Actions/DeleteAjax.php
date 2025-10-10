<?php

namespace FreeCRM\Modules\Calendar\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class DeleteAjax extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');

		$recordModel = \FreeCRM\Modules\Vtiger\Models\Record::getInstanceById($recordId, $moduleName);
		$recordModel->delete();

		$cvId = $request->get('viewname');
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array('viewname' => $cvId, 'module' => $moduleName));
		$response->emit();
	}
}
