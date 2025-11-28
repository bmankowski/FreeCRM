<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Performs basic validation on mapped staging rows.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;

class RecordValidator
{
	public const STATUS_OK = 'ok';
	public const STATUS_FAILED = 'failed';

	/**
	 * @param array<string, mixed> $values
	 */
	public function validate(ModuleModel $module, array $values, MappingDefinition $definition): array
	{
		$errors = [];

		foreach ($module->getFields() as $fieldModel) {
			if (!$fieldModel->isActiveField() || !$fieldModel->isEditable()) {
				continue;
			}

			if ($fieldModel->isMandatory()) {
				$fieldName = $fieldModel->getName();
				if (!array_key_exists($fieldName, $values) || $this->isEmpty($values[$fieldName])) {
					$errors[] = [
						'label' => $fieldModel->getFieldLabel(),
						'field' => $fieldName,
						'message' => \App\Language::translate('LBL_FIELD_IS_MANDATORY', $module->getName()),
					];
				}
			}
		}

		foreach ($definition->getDuplicateSets()['required'] as $set) {
			if (!$this->isSetSatisfied($set, $values)) {
				$errors[] = [
					'label' => implode(', ', $set),
					'field' => implode(',', $set),
					'message' => \App\Language::translate('LBL_DUPLICATE_KEY_MISSING', $module->getName()),
				];
			}
		}

		return [
			'status' => $errors ? self::STATUS_FAILED : self::STATUS_OK,
			'errors' => $errors,
		];
	}

	private function isEmpty($value): bool
	{
		return $value === null || $value === '' || (is_string($value) && trim($value) === '');
	}

	/**
	 * @param array<int, string> $set
	 * @param array<string, mixed> $values
	 */
	private function isSetSatisfied(array $set, array $values): bool
	{
		foreach ($set as $field) {
			if (!array_key_exists($field, $values) || $this->isEmpty($values[$field])) {
				return false;
			}
		}
		return true;
	}
}

