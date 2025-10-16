<?php

namespace FreeCRM\Modules\Settings\Vtiger\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class TaxAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('checkDuplicateName');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}

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

		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			$taxId = $taxRecordModel->save();
			$recordModel = Settings_Vtiger_TaxRecord_Model::getInstanceById($taxId, $type);
			$response->setResult(array_merge(array('_editurl' => $recordModel->getEditTaxUrl(), 'type' => $recordModel->getType(), 'row_type' => $currentUser->get('rowheight')), $recordModel->getData()));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function checkDuplicateName(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$taxId = $request->get('taxid');
		$taxLabel = $request->get('taxlabel');
		$type = $request->get('type');

		$exists = Settings_Vtiger_TaxRecord_Model::checkDuplicate($taxLabel, $taxId, $type);

		if (!$exists) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'message' => vtranslate('LBL_TAX_NAME_EXIST', $qualifiedModuleName));
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
