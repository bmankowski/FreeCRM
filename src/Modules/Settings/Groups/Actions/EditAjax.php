<?php

namespace App\Modules\Settings\Groups\Actions;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

Class Settings_Groups_EditAjax_Action extends \App\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('checkDuplicate');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function checkDuplicate(\App\Http\Vtiger_Request $request)
	{
		$groupName = $request->get('groupname');
		$recordId = $request->get('record');

		$recordModel = \App\Modules\Settings\Groups\Models\Record::getInstanceByName(\App\Utils\ListViewUtils::decodeHtml($groupName), array($recordId));

		$response = new \App\Http\Vtiger_Response();
		if (!empty($recordModel)) {
			$response->setResult(array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_DUPLICATES_EXIST', $request->getModule(false))));
		} else {
			$response->setResult(array('success' => false));
		}
		$response->emit();
	}
}
