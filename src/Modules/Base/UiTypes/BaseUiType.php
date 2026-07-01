<?php

namespace App\Modules\Base\UiTypes;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class BaseUiType extends \App\Runtime\BaseModel
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
	 * @param \App\Modules\Base\Models\Record|false $recordModel
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
	 * @param mixed $value
	 * @return mixed
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return $value;
	}

	/**
	 * Direct mapping from uitype integer to UiType class name suffix.
	 * This is the authoritative source for UI rendering — independent of getFieldDataType().
	 * Unmapped uitypes fall back to getFieldDataType() for backwards-compatible class resolution.
	 */
	private const UITYPE_CLASS_MAP = [
		3   => 'RecordNumber',
		4   => 'RecordNumber',
		8   => 'TotalTime',
		9   => 'Percentage',
		10  => 'Reference',
		11  => 'Phone',
		13  => 'Email',
		15  => 'Picklist',
		16  => 'Multipicklist',
		17  => 'Url',
		19  => 'Text',
		20  => 'Text',
		21  => 'Text',
		24  => 'Text',
		26  => 'Reference',
		28  => 'DocumentsFileUpload',
		30  => 'Recurrence',
		32  => 'Languages',
		33  => 'Multipicklist',
		50  => 'Reference',
		51  => 'Reference',
		52  => 'UserCreator',
		53  => 'Owner',
		54  => 'Multiowner',
		56  => 'Boolean',
		57  => 'Reference',
		58  => 'Reference',
		59  => 'Reference',
		61  => 'Image',
		66  => 'ReferenceProcess',
		67  => 'ReferenceLink',
		68  => 'ReferenceSubProcess',
		69  => 'Image',
		71  => 'Currency',
		72  => 'Currency',
		73  => 'Reference',
		75  => 'Reference',
		76  => 'Reference',
		80  => 'Reference',
		81  => 'Reference',
		85  => 'StringType',
		98  => 'UserRole',
		101 => 'Reference',
		117 => 'CurrencyList',
		120 => 'SharedOwner',
		156 => 'Boolean',
		300 => 'Text',
		301 => 'Modules',
		302 => 'Tree',
		303 => 'Taxes',
		304 => 'InventoryLimit',
		305 => 'MultiReferenceValue',
		306 => 'MultiReference',
		308 => 'RangeTime',
		309 => 'CategoryMultipicklist',
		310 => 'CompanySelect',
		311 => 'MultiImage',
		330 => 'MultiAttachmentJson',
		342 => 'Recurrence',
		357 => 'Reference',
		358 => 'MailSmtpSelect',
	];

	/**
	 * Resolves the UiType class name suffix for the given uitype integer.
	 * uitype 55 requires field-name discrimination (Salutation vs plain StringType).
	 * Everything not in UITYPE_CLASS_MAP falls back to getFieldDataType() for custom uitypes.
	 */
	private static function resolveUiTypeClassSuffix($fieldModel): string
	{
		$uitype = (int) $fieldModel->get('uitype');

		if ($uitype === 55) {
			return $fieldModel->getName() === 'salutationtype' ? 'Salutation' : 'StringType';
		}

		if (isset(self::UITYPE_CLASS_MAP[$uitype])) {
			return self::UITYPE_CLASS_MAP[$uitype];
		}

		// Custom or future uitypes: derive class suffix from semantic type string
		$dataType = $fieldModel->getFieldDataType();
		$suffix = ucfirst($dataType);
		return $suffix === 'String' ? 'StringType' : $suffix;
	}

	/**
	 * Returns a UiType instance for the given field model.
	 * Resolution order: module-specific class → Base class → BaseUiType fallback.
	 * @param \App\Modules\Base\Models\Field $fieldModel
	 * @return self
	 */
	public static function getInstanceFromField($fieldModel): self
	{
		$classSuffix = self::resolveUiTypeClassSuffix($fieldModel);
		$module = $fieldModel->getModuleName();

		$candidates = [
			"\\App\\Modules\\{$module}\\UiTypes\\{$classSuffix}",
			"\\App\\Modules\\Base\\UiTypes\\{$classSuffix}",
		];
		foreach ($candidates as $class) {
			if (class_exists($class)) {
				/** @var self $instance */
				$instance = new $class();
				$instance->set('field', $fieldModel);
				return $instance;
			}
		}
		$instance = new self();
		$instance->set('field', $fieldModel);
		return $instance;
	}

	/**
	 * Function to get the display value in edit view
	 * @param mixed $value
	 * @return mixed
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		return $value;
	}

	public function getListViewDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return \vtlib\Functions:: textLength($this->getDisplayValue($value, $record, $recordInstance, $rawText), $this->get('field')->get('maxlengthtext'));
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
	 * @return \App\Modules\Base\Models\Field
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
