<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Persists duplicate-detection rules selected by users and infers defaults.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;

class DuplicateRuleRepository
{
	private \yii\db\Connection $db;

	public function __construct(?\yii\db\Connection $db = null)
	{
		$this->db = $db ?? \App\Db\Db::getInstance();
	}

	/**
	 * Return previously saved duplicate sets for given module.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function find(string $module): array
	{
		$row = (new \App\Db\Query())
			->from('#__import_duplicate_rules')
			->where(['module' => $module])
			->limit(1)
			->one($this->db);

		if (!$row || empty($row['rules'])) {
			return [];
		}

		$decoded = \App\Utils\Json::decode($row['rules'], true);
		if (!\is_array($decoded)) {
			return [];
		}

		return $this->normalizeSets($decoded);
	}

	/**
	 * Persist duplicate sets for module (overwrites previous value).
	 *
	 * @param array<int, array<int, string>> $sets
	 */
	public function save(string $module, array $sets): void
	{
		$normalized = $this->normalizeSets($sets);
		if (!$normalized) {
			$this->delete($module);
			return;
		}
		$now = date('Y-m-d H:i:s');
		$data = [
			'module' => $module,
			'rules' => \App\Utils\Json::encode($normalized),
			'updated_at' => $now,
		];

		$exists = (new \App\Db\Query())
			->from('#__import_duplicate_rules')
			->where(['module' => $module])
			->exists($this->db);

		if ($exists) {
			$this->db->createCommand()
				->update('#__import_duplicate_rules', $data, ['module' => $module])
				->execute();
		} else {
			$data['created_at'] = $now;
			$this->db->createCommand()
				->insert('#__import_duplicate_rules', $data)
				->execute();
		}
	}

	public function delete(string $module): void
	{
		$this->db->createCommand()
			->delete('#__import_duplicate_rules', ['module' => $module])
			->execute();
	}

	/**
	 * Suggest duplicate sets using unique DB indexes.
	 *
	 * @return array<int, array<int, string>>
	 */
	public function suggest(ModuleModel $module): array
	{
		$fieldMapByTable = [];
		foreach ($module->getFields() as $fieldModel) {
			if (!$fieldModel->isActiveField() || !$fieldModel->isEditable()) {
				continue;
			}
			$table = $fieldModel->getTableName();
			$column = $fieldModel->getColumnName();
			if (!$table || !$column) {
				continue;
			}
			$table = (string) $table;
			$columnKey = strtolower((string) $column);
			$fieldMapByTable[$table][$columnKey] = $fieldModel->getName();
		}

		if (!$fieldMapByTable) {
			return [];
		}

		$suggestions = [];
		foreach ($fieldMapByTable as $table => $columns) {
			foreach ($this->fetchUniqueIndexColumns($table) as $segment) {
				$set = [];
				foreach ($segment as $columnName) {
					$key = strtolower($columnName);
					if (!isset($columns[$key])) {
						$set = [];
						break;
					}
					$set[] = $columns[$key];
				}
				if ($set) {
					$normalized = $this->normalizeSet($set);
					if ($normalized) {
						$suggestions[$this->serializeSet($normalized)] = $normalized;
					}
				}
			}
		}

		return array_values($suggestions);
	}

	/**
	 * @param array<int, mixed> $sets
	 * @return array<int, array<int, string>>
	 */
	private function normalizeSets(array $sets): array
	{
		$result = [];
		foreach ($sets as $set) {
			$normalized = $this->normalizeSet($set);
			if ($normalized) {
				$result[$this->serializeSet($normalized)] = $normalized;
			}
		}
		return array_values($result);
	}

	/**
	 * @param mixed $set
	 * @return array<int, string>
	 */
	private function normalizeSet($set): array
	{
		if (!\is_array($set)) {
			return [];
		}
		$fields = [];
		foreach ($set as $field) {
			$name = trim((string) $field);
			if ($name === '') {
				continue;
			}
			$fields[] = $name;
		}

		$fields = array_values(array_unique($fields));
		return $fields;
	}

	/**
	 * @return array<int, array<int, string>>
	 */
	private function fetchUniqueIndexColumns(string $table): array
	{
		$rows = $this->db->createCommand(
			'SELECT INDEX_NAME, COLUMN_NAME
			FROM INFORMATION_SCHEMA.STATISTICS
			WHERE TABLE_SCHEMA = DATABASE()
				AND TABLE_NAME = :table
				AND NON_UNIQUE = 0
				AND INDEX_NAME <> \'PRIMARY\'
			ORDER BY INDEX_NAME, SEQ_IN_INDEX',
			[':table' => $table]
		)->queryAll();

		$grouped = [];
		foreach ($rows as $row) {
			$index = $row['INDEX_NAME'] ?? null;
			$column = $row['COLUMN_NAME'] ?? null;
			if (!$index || !$column) {
				continue;
			}
			$grouped[$index][] = (string) $column;
		}

		return array_values($grouped);
	}

	/**
	 * Serialize set for deduplication (case-insensitive).
	 */
	private function serializeSet(array $set): string
	{
		$normalized = array_map(static fn($value) => strtolower((string) $value), $set);
		sort($normalized);
		return implode('::', $normalized);
	}
}

