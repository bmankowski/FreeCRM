<?php

namespace App\Modules\DocumentTemplates\UiTypes;

class Conditions extends \App\Modules\Base\UiTypes\BaseUiType
{
	public function getTemplateName()
	{
		return 'uitypes/document_template_conditions.tpl';
	}

	public function isAjaxEditable()
	{
		return false;
	}

	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if (is_array($value)) {
			return $value !== [] ? (string) count($value) : '';
		}
		if (is_string($value) && $value !== '') {
			$decoded = json_decode($value, true);
			return is_array($decoded) && $decoded !== [] ? (string) count($decoded) : '';
		}
		return '';
	}
}
