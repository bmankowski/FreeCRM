<?php

namespace FreeCRM\Modules\Vtiger\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class ExportData extends Action
{

	/**
	 * Function to check permission
	 * @param Vtiger_Request $request
	 * @throws \Exception\NoPermitted
	 */
	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		if (!Vtiger_Module_Model::getInstance($request->get('source_module'))->isPermitted('Export')) {
			throw new \Exception\NoPermittedToRecord('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * Function is called by the controller
	 * @param Vtiger_Request $request
	 */
	public function process(Vtiger_Request $request)
	{
		$exportModel = Vtiger_Export_Model::getInstanceFromRequest($request);
		if ($request->getMode() === 'ExportSelectedRecords') {
			$exportModel->setRecordList($this->getRecordsListFromRequest($request));
		}
		$exportModel->exportData($request);
	}
}
