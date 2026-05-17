<?php

namespace App\Modules\TemplateElements\UiTypes;

/**
 * String fields in TemplateElements; translates picklist-like type codes in list/detail views.
 */
class StringType extends \App\Modules\Base\UiTypes\BaseUiType
{
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if ($this->getFieldModel()->getName() === 'type' && $value !== '' && $value !== null) {
			return \App\Runtime\Vtiger_Language_Handler::translate((string) $value, $this->getFieldModel()->getModuleName());
		}

		return parent::getDisplayValue($value, $record, $recordInstance, $rawText);
	}
}
