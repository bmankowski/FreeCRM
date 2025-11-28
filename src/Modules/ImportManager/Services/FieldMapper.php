<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Maps raw input rows to module field values according to MappingDefinition.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;

class FieldMapper
{
	private ModuleModel $module;
	private MappingDefinition $definition;
	private array $fieldsMap = [];

	public function __construct(ModuleModel $module, MappingDefinition $definition)
	{
		$this->module = $module;
		$this->definition = $definition;
		foreach ($this->module->getFields() as $fieldModel) {
			$this->fieldsMap[$fieldModel->getName()] = $fieldModel;
		}
	}

	/**
	 * @param array<int, string|null> $row
	 *
	 * @return array<string, mixed>
	 */
	public function mapRow(array $row): array
	{
		$result = [];
		$mapping = $this->definition->getMapping();
		$defaults = $this->definition->getDefaultValues();

		foreach ($mapping as $map) {
			$fieldName = $map['field'];
			$value = null;
			if ($map['index'] !== null && array_key_exists($map['index'], $row)) {
				$value = $this->sanitizeValue($fieldName, $row[$map['index']]);
			}

			if (($value === null || $value === '') && array_key_exists($fieldName, $defaults)) {
				$value = $defaults[$fieldName];
			}

			$result[$fieldName] = $value;
		}

		return $result;
	}

	private function sanitizeValue(string $fieldName, $value)
	{
		if (!isset($this->fieldsMap[$fieldName])) {
			return $value;
		}
		$field = $this->fieldsMap[$fieldName];
		if ($value === null) {
			return null;
		}
		$value = is_string($value) ? trim($value) : $value;

		if ($value === '') {
			return '';
		}

		switch ($field->getFieldDataType()) {
			case 'integer':
			case 'double':
			case 'currency':
				return is_numeric($value) ? (string) $value : $value;
			default:
				return $value;
		}
	}
}

