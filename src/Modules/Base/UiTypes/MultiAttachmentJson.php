<?php

namespace App\Modules\Base\UiTypes;

/**
 * JSON payload for files served via module file handlers (e.g. Candidates cv_img_file / MultiAttachment).
 */
class MultiAttachmentJson extends BaseUiType
{
	public function isAjaxEditable()
	{
		return false;
	}

	public function isActiveSearchView()
	{
		return false;
	}

	public function isListviewSortable()
	{
		return false;
	}

	public function getDBValue($value, $recordModel = false)
	{
		if (is_array($value)) {
			return \App\Utils\Json::encode($value);
		}
		if ($value === null) {
			return '';
		}
		return (string) $value;
	}

	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if ($value === '' || $value === null) {
			return '';
		}
		$items = is_array($value) ? $value : \App\Utils\Json::decode((string) $value);
		if (!is_array($items) || $items === []) {
			return is_string($value) ? $value : '';
		}
		$names = [];
		foreach ($items as $item) {
			if (is_array($item) && !empty($item['name'])) {
				$names[] = (string) $item['name'];
			}
		}
		return $names !== [] ? implode(', ', $names) : '';
	}
}
