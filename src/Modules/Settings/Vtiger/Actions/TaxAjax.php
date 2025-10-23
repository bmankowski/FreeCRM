<?php

namespace App\Modules\Settings\Vtiger\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class TaxAjax extends \App\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('checkDuplicateName');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}

		// TODO: Settings_Vtiger_TaxRecord_Model class doesn't exist - needs implementation
		$response = new \App\Http\Vtiger_Response();
		$response->setError('NOT_IMPLEMENTED', 'Tax Record Model is not implemented yet');
		$response->emit();
		return;

		/* Commented out until Settings_Vtiger_TaxRecord_Model is implemented
		$taxId = $request->get('taxid');
		$type = $request->get('type');
		if (empty($taxId)) {
			$taxRecordModel = new Settings_Vtiger_TaxRecord_Model();
		} else {
			$taxRecordModel = Settings_Vtiger_TaxRecord_Model::getInstanceById($taxId, $type);
		}

		$fields = array('taxlabel', 'percentage', 'deleted');
		foreach ($fields as $fieldName) {
			if ($request->has($fieldName)) {
				$taxRecordModel->set($fieldName, $request->get($fieldName));
			}
		}

		$taxRecordModel->setType($type);

		$response = new \App\Http\Vtiger_Response();
		try {
			$taxId = $taxRecordModel->save();
			$recordModel = Settings_Vtiger_TaxRecord_Model::getInstanceById($taxId, $type);
			$response->setResult(array_merge(array('_editurl' => $recordModel->getEditTaxUrl(), 'type' => $recordModel->getType(), 'row_type' => $currentUser->get('rowheight')), $recordModel->getData()));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
		*/
	}

	public function checkDuplicateName(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$taxId = $request->get('taxid');
		$taxLabel = $request->get('taxlabel');
		$type = $request->get('type');

		// TODO: Settings_Vtiger_TaxRecord_Model class doesn't exist - needs implementation
		// $exists = Settings_Vtiger_TaxRecord_Model::checkDuplicate($taxLabel, $taxId, $type);
		$exists = false;

		if (!$exists) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_TAX_NAME_EXIST', $qualifiedModuleName));
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
