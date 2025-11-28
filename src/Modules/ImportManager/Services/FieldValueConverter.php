<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Converts raw staging values into database-friendly representations.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Field as FieldModel;
use App\Modules\Base\Models\Module as ModuleModel;

class FieldValueConverter
{
	/**
	 * Convert value for given field into DB friendly format.
	 *
	 * @param mixed $value
	 */
	public function convert(ModuleModel $module, string $fieldName, $value, ?int $fallbackOwnerId = null)
	{
		$fieldModel = $module->getFieldByName($fieldName);
		if (!$fieldModel) {
			return $value;
		}

		$normalized = $this->normalizeValue($value);
		if ($normalized === '' || $normalized === null) {
			return $normalized;
		}

		$dataType = $fieldModel->getFieldDataType();
		if ($dataType === 'owner') {
			$normalized = $this->resolveOwner($normalized, $fallbackOwnerId);
		} elseif ($dataType === 'sharedOwner') {
			$normalized = $this->resolveSharedOwner($normalized, $fallbackOwnerId);
		} elseif ($dataType === 'multipicklist') {
			$normalized = $this->resolveMultiPicklist($normalized);
		} elseif ($dataType === 'picklist') {
			$normalized = $this->resolvePicklist($fieldModel, $normalized);
		} elseif ($dataType === 'tree' || $dataType === 'categoryMultipicklist') {
			$normalized = $this->resolveTree($fieldModel, $normalized);
		} elseif (in_array($dataType, FieldModel::$referenceTypes, true)) {
			$normalized = $this->resolveReference($fieldModel, $normalized);
		} elseif ($dataType === 'date') {
			$normalized = $this->resolveDate($normalized);
		} elseif ($dataType === 'datetime') {
			$normalized = $this->resolveDateTime($normalized);
		}

		return $fieldModel->getDBValue($normalized);
	}

	/**
	 * Normalize raw value (handle JSON/arrays).
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	private function normalizeValue($value)
	{
		if (is_array($value)) {
			return $value;
		}
		if ($value === null) {
			return null;
		}
		if (is_string($value)) {
			$trimmed = trim($value);
			if ($trimmed === '') {
				return '';
			}
			if ($this->looksLikeJson($trimmed)) {
				$decoded = \App\Utils\Json::decode($trimmed, true);
				if ($decoded !== null) {
					return $decoded;
				}
			}
			return $trimmed;
		}
		return $value;
	}

	/**
	 * Convert owner (assigned_user_id) value to numeric id.
	 *
	 * @param mixed $value
	 */
	private function resolveOwner($value, ?int $fallbackOwnerId): ?int
	{
		if (is_int($value) || (is_string($value) && ctype_digit($value))) {
			return (int) $value;
		}

		$ownerId = \App\Modules\Users\Models\Record::getUserIdByName((string) $value);
		if (!$ownerId) {
			$ownerId = \App\Fields\Owner::getGroupId((string) $value);
		}
		if (!$ownerId) {
			$ownerId = $fallbackOwnerId ?: \App\Modules\Users\Models\Record::getCurrentUserId();
		}
		return $ownerId ? (int) $ownerId : null;
	}

	/**
	 * Resolve shared owner values (expects list of IDs).
	 *
	 * @param mixed $value
	 * @return array<int>
	 */
	private function resolveSharedOwner($value, ?int $fallbackOwnerId): array
	{
		$values = is_array($value) ? $value : explode(',', (string) $value);
		$result = [];
		foreach ($values as $entry) {
			$entry = trim((string) $entry);
			if ($entry === '') {
				continue;
			}
			$resolved = $this->resolveOwner($entry, $fallbackOwnerId);
			if ($resolved) {
				$result[] = $resolved;
			}
		}
		return array_values(array_unique($result));
	}

	/**
	 * Normalize multi picklist string.
	 *
	 * @param mixed $value
	 */
	private function resolveMultiPicklist($value): string
	{
		if (is_array($value)) {
			$values = array_map('trim', $value);
		} else {
			$delimiter = str_contains((string) $value, ' |##| ') ? ' |##| ' : ',';
			$values = array_map('trim', explode($delimiter, (string) $value));
		}
		$values = array_filter($values, static fn($item) => $item !== '');
		return implode(' |##| ', $values);
	}

	private function resolvePicklist(FieldModel $fieldModel, $value)
	{
		if ($value === '' || $value === null) {
			return $value;
		}
		$available = $fieldModel->getPicklistValues();
		if (!$available) {
			return $value;
		}
		$normalized = strtolower(htmlentities((string) $value, ENT_QUOTES, \App\Core\AppConfig::main('default_charset', 'UTF-8')));
		foreach ($available as $dbValue => $label) {
			if (strtolower($dbValue) === $normalized || strtolower($label) === $normalized) {
				return $dbValue;
			}
		}
		return $value;
	}

	private function resolveTree(FieldModel $fieldModel, $value)
	{
		if ($value === '' || $value === null) {
			return $value;
		}
		$trees = \App\Fields\Tree::getValuesById((int) $fieldModel->getFieldParams());
		foreach ($trees as $tree) {
			if ($tree['name'] === $value || $tree['tree'] === $value) {
				return $tree['tree'];
			}
		}
		return $value;
	}

	/**
	 * Resolve reference field value to crm id.
	 *
	 * @param mixed $value
	 */
	private function resolveReference(FieldModel $fieldModel, $value): ?int
	{
		if ($value === '' || $value === null) {
			return null;
		}
		$module = null;
		$label = null;
		if (is_string($value) && str_contains($value, '::::')) {
			[$module, $label] = explode('::::', $value, 2);
		} elseif (is_string($value) && str_contains($value, ':::')) {
			[$module, $label] = explode(':::', $value, 2);
		} else {
			$label = (string) $value;
		}

		if ($module) {
			$module = trim($module);
			$label = trim((string) $label);
			if ($module === 'Users') {
				return \App\Modules\Users\Models\Record::getUserIdByName($label) ?: null;
			}
			return \App\Records\Record::getCrmIdByLabel($module, \App\Utils\ListViewUtils::decodeHtml($label)) ?: null;
		}

		foreach ($fieldModel->getReferenceList() as $candidate) {
			if ($candidate === 'Users') {
				$userId = \App\Modules\Users\Models\Record::getUserIdByName((string) $value);
				if ($userId) {
					return $userId;
				}
				continue;
			}
			$recordId = \App\Records\Record::getCrmIdByLabel($candidate, \App\Utils\ListViewUtils::decodeHtml((string) $value));
			if ($recordId) {
				return $recordId;
			}
		}

		return null;
	}

	private function resolveDate($value): string
	{
		if ($value === '' || $value === null) {
			return '';
		}
		return \App\Utils\Utils::getValidDBInsertDateValue((string) $value);
	}

	private function resolveDateTime($value): string
	{
		if ($value === '' || $value === null) {
			return '';
		}
		return \App\Utils\Utils::getValidDBInsertDateTimeValue((string) $value);
	}

	private function looksLikeJson(string $value): bool
	{
		$value = trim($value);
		return (str_starts_with($value, '{') && str_ends_with($value, '}'))
			|| (str_starts_with($value, '[') && str_ends_with($value, ']'));
	}
}

