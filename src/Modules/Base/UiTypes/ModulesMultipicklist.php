<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Base\UiTypes;

class ModulesMultipicklist extends Multipicklist
{
	public function getTemplateName(): string
	{
		return 'uitypes/ModulesMultipicklist.tpl';
	}

	public function getListSearchTemplateName(): string
	{
		return 'uitypes/ModulesMultipicklistFieldSearchView.tpl';
	}

	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		if ($value === null || $value === '') {
			return '';
		}
		$parts = is_array($value) ? $value : explode(' |##| ', (string) $value);
		$labels = [];
		foreach ($parts as $part) {
			$part = trim((string) $part);
			if ($part !== '') {
				$labels[] = \App\Runtime\Vtiger_Language_Handler::translate($part, $part);
			}
		}

		return $labels === [] ? '' : implode(', ', $labels);
	}

	public function getEditViewDisplayValue($value, $record = false): array
	{
		if ($value === null || $value === '') {
			return [];
		}
		if (is_array($value)) {
			return array_values(array_filter(array_map('trim', $value)));
		}

		return array_values(array_filter(array_map('trim', explode(' |##| ', (string) $value))));
	}
}
