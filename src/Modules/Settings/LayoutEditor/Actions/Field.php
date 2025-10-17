<?php

namespace FreeCRM\Modules\Settings\LayoutEditor\Actions;
use FreeCRM\Modules\Settings\LayoutEditor\Models\Field as Settings_LayoutEditor_Field_Model;
use FreeCRM\Modules\Settings\LayoutEditor\Models\Block as Settings_LayoutEditor_Block_Model;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Field extends \FreeCRM\Modules\Settings\Vtiger\Actions\Index
{

	public function __construct()
	{
		$this->exposeMethod('add');
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
		$this->exposeMethod('move');
		$this->exposeMethod('unHide');
		$this->exposeMethod('getPicklist');
	}

	public function add(\FreeCRM\Http\Vtiger_Request $request)
	{
		$type = $request->get('fieldType');
		$moduleName = $request->get('sourceModule');
		$blockId = $request->get('blockid');
		$moduleModel = \FreeCRM\Modules\Settings\LayoutEditor\Models\Module::getInstanceByName($moduleName);
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			$fieldModel = $moduleModel->addField($type, $blockId, $request->getAll());
			$fieldInfo = $fieldModel->getFieldInfo();
			$responseData = array_merge([
				'id' => $fieldModel->getId(),
				'name' => $fieldModel->get('name'),
				'blockid' => $blockId,
				'customField' => $fieldModel->isCustomField()], $fieldInfo);
			$response->setResult($responseData);
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function save(\FreeCRM\Http\Vtiger_Request $request)
	{
		$fieldId = $request->get('fieldid');
		$fieldInstance = \FreeCRM\Modules\Vtiger\Models\Field::getInstance($fieldId);
		$fields = ['presence', 'quickcreate', 'summaryfield', 'helpinfo', 'generatedtype', 'masseditable', 'header_field', 'displaytype', 'maxlengthtext', 'maxwidthcolumn'];
		foreach ($request->getAll() as $key => $value) {
			if ($key == 'mandatory') {
				$fieldInstance->updateTypeofDataFromMandatory($value);
			}
			if (in_array($key, $fields)) {
				$fieldInstance->set($key, $value);
			}
		}
		$defaultValue = $request->get('fieldDefaultValue');
		if ($fieldInstance->getFieldDataType() == 'date') {
			$dateInstance = new \Vtiger_Date_UIType();
			$defaultValue = $dateInstance->getDBInsertedValue($defaultValue);
		}
		if ($request->has('fieldMask')) {
			$fieldInstance->set('fieldparams', $request->get('fieldMask'));
		}
		if (is_array($defaultValue)) {
			$defaultValue = implode(' |##| ', $defaultValue);
		}
		$fieldInstance->set('defaultvalue', $defaultValue);
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			$fieldInstance->save();
			$response->setResult([
				'success' => true,
				'presence' => $request->get('presence'),
				'mandatory' => $fieldInstance->isMandatory(),
				'label' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate($fieldInstance->get('label'), $request->get('sourceModule'))]);
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function delete(\FreeCRM\Http\Vtiger_Request $request)
	{
		$fieldId = $request->get('fieldid');
		$fieldInstance = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
		$response = new \FreeCRM\Http\Vtiger_Response();

		if (!$fieldInstance->isCustomField()) {
			$response->setError('122', 'Cannot delete Non custom field');
			$response->emit();
			return;
		}

		try {
			$fieldInstance->delete();
			$response->setResult(array('success' => true));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function move(\FreeCRM\Http\Vtiger_Request $request)
	{
		$updatedFieldsList = $request->get('updatedFields');

		//This will update the fields sequence for the updated blocks
		Settings_LayoutEditor_Block_Model::updateFieldSequenceNumber($updatedFieldsList);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(array('success' => true));
		$response->emit();
	}

	public function unHide(\FreeCRM\Http\Vtiger_Request $request)
	{
		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			$fieldIds = $request->get('fieldIdList');
			Settings_LayoutEditor_Field_Model::makeFieldActive($fieldIds, $request->get('blockId'));
			$responseData = array();
			foreach ($fieldIds as $fieldId) {
				$fieldModel = Settings_LayoutEditor_Field_Model::getInstance($fieldId);
				$fieldInfo = $fieldModel->getFieldInfo();
				$responseData[] = array_merge(array('id' => $fieldModel->getId(), 'blockid' => $fieldModel->get('block')->id, 'customField' => $fieldModel->isCustomField()), $fieldInfo);
			}
			$response->setResult($responseData);
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function getPicklist(\FreeCRM\Http\Vtiger_Request $request)
	{
		$response = new \FreeCRM\Http\Vtiger_Response();
		$fieldName = $request->get('rfield');
		$moduleName = $request->get('rmodule');
		$picklistValues = [];
		if (!empty($fieldName) && !empty($moduleName) && $fieldName != '-') {
			$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
			$fieldInstance = \FreeCRM\Modules\Vtiger\Models\Field::getInstance($fieldName, $moduleModel);
			$picklistValues = $fieldInstance->getPicklistValues();
			if ($picklistValues === null) {
				$picklistValues = [];
			}
		}
		$response->setResult($picklistValues);
		$response->emit();
	}

	public function validateRequest(\FreeCRM\Http\Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
