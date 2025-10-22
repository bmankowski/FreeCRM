<?php

namespace App\Modules\Vtiger\UiTypes;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class Base extends \App\Runtime\BaseModel
{

	public function isAjaxEditable()
	{
		return true;
	}

	/**
	 * If the field is sortable in ListView
	 */
	public function isListviewSortable()
	{
		return true;
	}

	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/String.tpl';
	}

	/**
	 * Function to get the DB Insert Value, for the current field type with given User Value
	 * @param mixed $value
	 * @param \App\Modules\Vtiger\Models\Record $recordModel
	 * @return mixed
	 */
	public function getDBValue($value, $recordModel = false)
	{
		if ($value === '' && in_array($this->getFieldModel()->getFieldType(), ['I', 'N', 'NN'])) {
			$value = 0;
		}
		if (is_null($value)) {
			$value = '';
		}
		return $value;
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return $value;
	}

	/**
	 * Static function to get the UIType object from Vtiger Field Model
	 * @param \App\Modules\Vtiger\Models\Field $fieldModel
	 * @return \App\Modules\Vtiger\UiTypes\Base or UIType specific object instance
	 */
	public static function getInstanceFromField($fieldModel)
	{
		$fieldDataType = $fieldModel->getFieldDataType();
		$uiTypeClassSuffix = ucfirst($fieldDataType);
		$moduleName = $fieldModel->getModuleName();
		$moduleSpecificUiTypeClassName = '\App\Modules\\' . $moduleName . '\\UiTypes\\' . $uiTypeClassSuffix;
		$uiTypeClassName = '\App\Modules\\Vtiger\\UiTypes\\' . $uiTypeClassSuffix;
		$fallBackClassName = '\App\Modules\\Vtiger\\UiTypes\\Base';

		// Try PSR-4 paths first, then fallback to old paths
		$moduleSpecificFilePath = ROOT_DIRECTORY . '/src/Modules/' . $moduleName . '/UiTypes/' . $uiTypeClassSuffix . '.php';
		$completeFilePath = ROOT_DIRECTORY . '/src/Modules/Vtiger/UiTypes/' . $uiTypeClassSuffix . '.php';
		
		// Old path fallback
		$moduleSpecificFileName = 'modules.' . $moduleName . '.uitypes.' . $uiTypeClassSuffix;
		$uiTypeClassFileName = 'modules.Vtiger.uitypes.' . $uiTypeClassSuffix;
		$moduleSpecificOldFilePath = \App\Vtiger_Loader::resolveNameToPath($moduleSpecificFileName);
		$completeOldFilePath = \App\Vtiger_Loader::resolveNameToPath($uiTypeClassFileName);

		if (file_exists($moduleSpecificFilePath) && class_exists($moduleSpecificUiTypeClassName)) {
			$instance = new $moduleSpecificUiTypeClassName();
		} else if (file_exists($completeFilePath) && class_exists($uiTypeClassName)) {
			$instance = new $uiTypeClassName();
		} else if (file_exists($moduleSpecificOldFilePath) && class_exists($moduleSpecificUiTypeClassName)) {
			$instance = new $moduleSpecificUiTypeClassName();
		} else if (file_exists($completeOldFilePath) && class_exists($uiTypeClassName)) {
			$instance = new $uiTypeClassName();
		} else {
			$instance = new $fallBackClassName();
		}
		$instance->set('field', $fieldModel);
		return $instance;
	}

	/**
	 * Function to get the display value in edit view
	 * @param reference record id
	 * @return link
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		return $value;
	}

	public function getListViewDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return \vtlib\Functions::textLength($this->getDisplayValue($value, $record, $recordInstance, $rawText), $this->get('field')->get('maxlengthtext'));
	}

	public function getRelatedListViewDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return $this->getListViewDisplayValue($value, $record, $recordInstance, $rawText);
	}

	/**
	 * Function to get the Detailview template name for the current UI Type Object
	 * @return string - Template Name
	 */
	public function getDetailViewTemplateName()
	{
		return 'uitypes/StringDetailView.tpl';
	}

	/**
	 * Function to get Display value for RelatedList
	 * @param string $value
	 * @return string
	 */
	public function getRelatedListDisplayValue($value)
	{
		return $this->getDisplayValue($value);
	}

	public function getListSearchTemplateName()
	{
		return 'uitypes/FieldSearchView.tpl';
	}

	/**
	 * Get field model instance
	 * @return \App\Modules\Vtiger\Models\Field
	 */
	public function getFieldModel()
	{
		return $this->get('field');
	}

	public function isActiveSearchView()
	{
		return true;
	}
}
