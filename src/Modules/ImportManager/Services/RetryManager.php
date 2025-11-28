<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Handles export, inline edits and retry metadata for failed staging rows.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;

class RetryManager
{
	private BatchRepository $batches;
	private MappingRepository $mappings;
	private TemporaryTableManager $tables;
	private ConfigProvider $config;
	private \yii\db\Connection $db;

	public function __construct(
		?BatchRepository $batches = null,
		?MappingRepository $mappings = null,
		?TemporaryTableManager $tables = null,
		?ConfigProvider $config = null,
		?\yii\db\Connection $db = null
	) {
		$this->batches = $batches ?? new BatchRepository();
		$this->mappings = $mappings ?? new MappingRepository();
		$this->tables = $tables ?? new TemporaryTableManager();
		$this->config = $config ?? new ConfigProvider();
		$this->db = $db ?? \App\Db\Db::getInstance();
	}

	public function getFailedRows(int $batchId, int $limit = 50, int $offset = 0): array
	{
		$context = $this->buildContext($batchId);
		$table = $context['table'];
		$mapping = $context['definition']->getMapping();
		if (!$mapping) {
			return ['rows' => [], 'total' => 0];
		}
		if (!$this->tableExists($table)) {
			return ['rows' => [], 'total' => 0, 'limit' => $limit, 'offset' => $offset];
		}

		$columns = ['`id`', '`row_number`', '`error_payload`'];
		foreach ($mapping as $map) {
			$column = $this->tables->getColumnName($map['field']);
			$columns[] = sprintf('`%s` AS `%s`', $column, $map['field']);
		}

		$total = (new \App\Db\Query())
			->from($table)
			->where(['validation_status' => RecordValidator::STATUS_FAILED])
			->count('*', $this->db);

		if ($total === 0) {
			return ['rows' => [], 'total' => 0];
		}

		$rows = (new \App\Db\Query())
			->select(implode(',', $columns))
			->from($table)
			->where(['validation_status' => RecordValidator::STATUS_FAILED])
			->orderBy(['row_number' => SORT_ASC])
			->limit($limit)
			->offset($offset)
			->all($this->db);

		$normalized = array_map(static function ($row) use ($mapping) {
			$values = [];
			foreach ($mapping as $map) {
				$field = $map['field'];
				$values[$field] = $row[$field] ?? null;
			}
			return [
				'rowNumber' => (int) $row['row_number'],
				'errors' => $row['error_payload'] ? (\App\Utils\Json::decode($row['error_payload'], true) ?? []) : [],
				'values' => $values,
			];
		}, $rows);

		return [
			'rows' => $normalized,
			'total' => (int) $total,
			'limit' => $limit,
			'offset' => $offset,
		];
	}

	/**
	 * @param array<int, array{rowNumber:int, values:array<string, string|null>}> $rows
	 */
	public function updateRows(int $batchId, array $rows): int
	{
		if (!$rows) {
			return 0;
		}
		$context = $this->buildContext($batchId);
		$table = $context['table'];

		$this->ensureBatchEditable($context['batch']);
		if (!$this->tableExists($table)) {
			return 0;
		}

		$updated = 0;
		foreach ($rows as $row) {
			if (empty($row['rowNumber']) || empty($row['values']) || !is_array($row['values'])) {
				continue;
			}
			$updateData = [
				'validation_status' => 'pending',
				'error_payload' => null,
				'retry_token' => hash('sha256', microtime(true) . $batchId . $row['rowNumber']),
			];
			foreach ($row['values'] as $field => $value) {
				$column = $this->tables->getColumnName($field);
				$updateData[$column] = $value;
			}

			$affected = $this->db->createCommand()
				->update(
					$table,
					$updateData,
					[
						'row_number' => (int) $row['rowNumber'],
						'validation_status' => RecordValidator::STATUS_FAILED,
					]
				)
				->execute();

			$updated += $affected;
		}

		return $updated;
	}

	public function streamFailedRowsCsv(int $batchId, callable $writer): void
	{
		$context = $this->buildContext($batchId);
		$table = $context['table'];
		$mapping = $context['definition']->getMapping();
		if (!$mapping) {
			return;
		}
		if (!$this->tableExists($table)) {
			return;
		}

		$options = $context['definition']->getOptions();
		$sourceHeaders = $options['sourceHeaders'] ?? [];
		$header = ['row_number'];
		foreach ($mapping as $map) {
			$header[] = $this->resolveHeaderLabel($map, $sourceHeaders);
		}
		$header[] = 'errors';
		$writer($header);

		$query = (new \App\Db\Query())
			->from($table)
			->where(['validation_status' => RecordValidator::STATUS_FAILED])
			->orderBy(['row_number' => SORT_ASC]);

		foreach ($query->batch(200, $this->db) as $rows) {
			foreach ($rows as $row) {
				$line = [(int) $row['row_number']];
				foreach ($mapping as $map) {
					$column = $this->tables->getColumnName($map['field']);
					$value = $row[$column] ?? '';
					if ($value !== null && $value !== '' && $this->looksLikeJson($value)) {
						$decoded = \App\Utils\Json::decode($value, true);
						if ($decoded !== null) {
							$value = $this->stringifyCsvValue($decoded);
						}
					} elseif (is_array($value)) {
						$value = $this->stringifyCsvValue($value);
					}
					$line[] = $value;
				}
				$errorPayload = $row['error_payload'] ? \App\Utils\Json::decode($row['error_payload'], true) : null;
				$errors = $errorPayload ? $this->formatErrors($errorPayload) : '';
				$line[] = $errors;
				$writer($line);
			}
		}
	}

	private function buildContext(int $batchId): array
	{
		$batch = $this->batches->find($batchId);
		if (!$batch) {
			throw new \RuntimeException('Nie znaleziono wsadu.');
		}

		$module = ModuleModel::getInstance($batch['module']);
		if (!$module) {
			throw new \RuntimeException('Nie można zainicjować modułu docelowego.');
		}

		$mappingRow = $this->mappings->findByBatch($batchId);
		if (!$mappingRow) {
			throw new \RuntimeException('Brakuje zdefiniowanego mapowania dla wsadu.');
		}

		$definition = MappingDefinition::fromDatabaseRow(
			$mappingRow,
			$module,
			$this->config,
			$batch['duplicate_strategy'] ?? null
		);

		return [
			'batch' => $batch,
			'module' => $module,
			'definition' => $definition,
			'table' => $this->tables->getTableName($module->getName(), $batchId),
		];
	}

	private function ensureBatchEditable(array $batch): void
	{
		if (!empty($batch['status']) && $batch['status'] === 'running') {
			throw new \RuntimeException('Nie można edytować wsadu w trakcie przetwarzania.');
		}
	}

	private function tableExists(string $tableName): bool
	{
		return (bool) $this->db->getTableSchema($tableName, true);
	}

	private function resolveHeaderLabel(array $map, array $sourceHeaders): string
	{
		if (!empty($map['column'])) {
			return $map['column'];
		}
		if (isset($map['index'], $sourceHeaders[$map['index']])) {
			return $sourceHeaders[$map['index']];
		}
		if (!empty($map['label'])) {
			return $map['label'];
		}
		return $map['field'];
	}

	/**
	 * @param mixed $payload
	 */
	private function formatErrors($payload): string
	{
		if (!is_array($payload)) {
			return $payload === null ? '' : (string) $payload;
		}

		$messages = [];
		foreach ($payload as $item) {
			if (is_array($item)) {
				$label = $item['label'] ?? null;
				$message = $item['message'] ?? null;
				$parts = [];
				if ($label) {
					$parts[] = $label;
				}
				if ($message) {
					$parts[] = $message;
				}
				if ($parts) {
					$messages[] = implode(': ', $parts);
				} else {
					$nested = $this->formatErrors($item);
					if ($nested !== '') {
						$messages[] = $nested;
					}
				}
			} elseif ($item !== null && $item !== '') {
				$messages[] = (string) $item;
			}
		}

		$messages = array_filter($messages, static fn($msg) => $msg !== '');
		return implode('; ', $messages);
	}

	/**
	 * Normalize nested JSON-like values into human readable CSV strings.
	 *
	 * @param mixed $value
	 */
	private function stringifyCsvValue($value): string
	{
		if (is_array($value)) {
			$isAssoc = !array_is_list($value);
			$parts = [];
			foreach ($value as $key => $item) {
				$stringified = $this->stringifyCsvValue($item);
				if ($stringified === '') {
					continue;
				}
				$parts[] = $isAssoc ? sprintf('%s: %s', (string) $key, $stringified) : $stringified;
			}
			return $parts ? implode(', ', $parts) : '';
		}
		if ($value === null) {
			return '';
		}
		if (is_bool($value)) {
			return $value ? 'true' : 'false';
		}
		if (is_scalar($value)) {
			return (string) $value;
		}
		return \App\Utils\Json::encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}

	private function looksLikeJson($value): bool
	{
		if (!is_string($value) || $value === '') {
			return false;
		}
		$value = trim($value);
		return (str_starts_with($value, '{') && str_ends_with($value, '}'))
			|| (str_starts_with($value, '[') && str_ends_with($value, ']'));
	}
}

