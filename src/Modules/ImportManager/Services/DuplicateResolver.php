<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Finds existing CRM records that match staging rows according to duplicate sets.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;
use App\QueryField\QueryGenerator;

class DuplicateResolver
{
	private FieldValueConverter $converter;

	public function __construct(?FieldValueConverter $converter = null)
	{
		$this->converter = $converter ?? new FieldValueConverter();
	}

	/**
	 * Attempt to resolve duplicate record id for given row.
	 *
	 * @param array<string, mixed> $values
	 */
	public function find(
		ModuleModel $module,
		MappingDefinition $definition,
		array $values,
		int $fallbackOwnerId
	): ?int {
		$sets = $this->buildSets($definition);
		if (!$sets) {
			return null;
		}

		foreach ($sets as $set) {
			$conditions = [];
			foreach ($set as $fieldName) {
				if (!array_key_exists($fieldName, $values)) {
					$conditions = [];
					break;
				}
				$value = $values[$fieldName];
				if ($value === '' || $value === null) {
					$conditions = [];
					break;
				}
				$converted = $this->converter->convert($module, $fieldName, $value, $fallbackOwnerId);
				if ($converted === '' || $converted === null) {
					$conditions = [];
					break;
				}
				$conditions[$fieldName] = $converted;
			}

			if (!$conditions) {
				continue;
			}

			$queryGenerator = new QueryGenerator($module->getName());
			$queryGenerator->setFields(['id']);
			foreach ($conditions as $fieldName => $convertedValue) {
				$queryGenerator->addCondition($fieldName, $convertedValue, 'e');
			}
			$query = $queryGenerator->createQuery();
			$query->limit(1);
			$recordId = $query->scalar();
			if ($recordId) {
				return (int) $recordId;
			}
		}

		return null;
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	private function buildSets(MappingDefinition $definition): array
	{
		$config = $definition->getDuplicateSets();
		$sets = [];
		foreach ($config['required'] ?? [] as $set) {
			if (is_array($set) && $set) {
				$sets[] = array_values($set);
			}
		}
		foreach ($config['optional'] ?? [] as $set) {
			if (is_array($set) && $set) {
				$sets[] = array_values($set);
			}
		}
		return $sets;
	}
}

