<?php

namespace App\Modules\PBXManager\Views;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */


use App\Http\Vtiger_Request;

class Detail  extends \App\Modules\Vtiger\Views\Index
{

	/**
	 * Overrided to disable Ajax Edit option in Detail View of
	 * PBXManager Record
	 */
	public function isAjaxEnabled($recordModel)
	{
		return false;
	}
	/*
	 * Overided to convert totalduration to minutes
	 */

	public function preProcess(\App\Http\Vtiger_Request $request, $display = true)
	{
		$recordId = $request->get('record');
		$moduleName = $request->getModule();
		if (!$this->record) {
			$this->record = \App\Modules\Vtiger\Models\DetailView::getInstance($moduleName, $recordId);
		}
		$recordModel = $this->record->getRecord();

		// To show recording link only if callstatus is 'completed' 
		if ($recordModel->get('callstatus') != 'completed') {
			$recordModel->set('recordingurl', '');
		}
		return parent::preProcess($request, true);
	}
}
