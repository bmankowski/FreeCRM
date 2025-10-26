<?php

namespace App\Modules\Settings\Vtiger\Actions;
use App\Modules\Settings\Vtiger\Models\CustomRecordNumberingModule;


/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class CustomRecordNumberingAjax extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('getModuleCustomNumberingData');
		$this->exposeMethod('saveModuleCustomNumberingData');
		$this->exposeMethod('updateRecordsWithSequenceNumber');
	}

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		parent::checkPermission($request);
		$qualifiedModuleName = $request->getModule(false);
		$sourceModule = $request->get('sourceModule');

		if (!$sourceModule) {
			throw new \Exception\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_PERMISSION_DENIED', $qualifiedModuleName));
		}
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function to get Module custom numbering data
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function getModuleCustomNumberingData(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');
		$moduleData = \App\Fields\RecordNumber::getNumber($sourceModule);

		$response = new \App\Http\Vtiger_Response();
		$response->setEmitType(\App\Http\Vtiger_Response::$EMIT_JSON);
		$response->setResult($moduleData);
		$response->emit();
	}

	/**
	 * Function save module custom numbering data
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function saveModuleCustomNumberingData(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$moduleModel = \App\Modules\Settings\Vtiger\Models\CustomRecordNumberingModule::getInstance($request->get('sourceModule'));
		$moduleModel->set('prefix', $request->get('prefix'));
		$moduleModel->set('sequenceNumber', $request->get('sequenceNumber'));
		$moduleModel->set('postfix', $request->get('postfix'));
		$result = $moduleModel->setModuleSequence();
		$response = new \App\Http\Vtiger_Response();
		if ($result['success']) {
			$response->setResult(\App\Runtime\Vtiger_Language_Handler::translate('LBL_SUCCESSFULLY_UPDATED', $qualifiedModuleName));
		} else {
			$message = \App\Runtime\Vtiger_Language_Handler::translate('LBL_PREFIX_IN_USE', $qualifiedModuleName);
			$response->setError($message);
		}
		$response->emit();
	}

	/**
	 * Function to update record with sequence number
	 * @param \App\Http\Vtiger_Request $request
	 */
	public function updateRecordsWithSequenceNumber(\App\Http\Vtiger_Request $request)
	{
		$sourceModule = $request->get('sourceModule');

		$moduleModel = \App\Modules\Settings\Vtiger\Models\CustomRecordNumberingModule::getInstance($sourceModule);
		$result = $moduleModel->updateRecordsWithSequence();

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
