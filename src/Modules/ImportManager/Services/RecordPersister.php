<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Persists validated staging rows into CRM modules.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;
use App\Modules\Base\Models\Record;

class RecordPersister
{
	private TemporaryTableManager $tables;
	private DuplicateResolver $duplicates;
	private ImportLogService $logService;
	private FieldValueConverter $converter;
	private ConfigProvider $config;
	private \yii\db\Connection $db;

	public function __construct(
		?TemporaryTableManager $tables = null,
		?DuplicateResolver $duplicates = null,
		?ImportLogService $logService = null,
		?FieldValueConverter $converter = null,
		?ConfigProvider $config = null,
		?\yii\db\Connection $db = null
	) {
		$this->tables = $tables ?? new TemporaryTableManager();
		$this->duplicates = $duplicates ?? new DuplicateResolver();
		$this->logService = $logService ?? new ImportLogService();
		$this->converter = $converter ?? new FieldValueConverter();
		$this->config = $config ?? new ConfigProvider();
		$this->db = $db ?? \App\Db\Db::getInstance();
	}

	/**
	 * @return array<string, int>
	 */
	public function persist(array $context): array
	{
		$batch = $context['batch'];
		$module = $context['module'];
		$definition = $context['definition'];
		$table = $context['table'];
		$userId = (int) $batch['created_by'];

		$stats = [
			'total' => 0,
			'created' => 0,
			'updated' => 0,
			'skipped' => 0,
			'failed' => 0,
		];

		$query = (new \App\Db\Query())
			->from($table)
			->where(['validation_status' => RecordValidator::STATUS_OK])
			->orderBy(['id' => SORT_ASC]);

		$chunkSize = $this->config->getChunkSize();
		foreach ($query->batch($chunkSize, $this->db) as $rows) {
			$transaction = $this->db->beginTransaction();
			try {
				foreach ($rows as $row) {
					$stats['total']++;
					$result = $this->processRow($row, $module, $definition, $table, $userId);
					$stats[$result] = ($stats[$result] ?? 0) + 1;
				}
				$transaction->commit();
			} catch (\Throwable $exception) {
				$transaction->rollBack();
				throw $exception;
			}
		}

		return $stats;
	}

	/**
	 * Process single staging row.
	 */
	private function processRow(
		array $row,
		ModuleModel $module,
		MappingDefinition $definition,
		string $table,
		int $userId
	): string {
		$rowId = (int) $row['id'];
		$rowNumber = (int) $row['row_number'];
		$values = $this->extractValues($row, $definition);
		$converted = [];
		foreach ($values as $fieldName => $value) {
			$converted[$fieldName] = $this->converter->convert($module, $fieldName, $value, $userId);
		}

		try {
			$recordId = $this->duplicates->find($module, $definition, $converted, $userId);
			if ($recordId) {
				$status = $this->handleDuplicate($recordId, $module, $converted, $definition->getDuplicateStrategy());
			} else {
				$status = $this->createRecord($module->getName(), $converted, $userId);
			}

			$this->updateRowStatus($table, $rowId, $status['state'], $status['message']);
			$this->logService->log(
				$definition->getBatchId(),
				'import',
				$status['logStatus'],
				$status['message'],
				['recordId' => $status['recordId'], 'action' => $status['action']],
				$rowNumber,
				$status['recordId']
			);
			return $status['statBucket'];
		} catch (\Throwable $exception) {
			$message = $exception->getMessage();
			$this->updateRowStatus($table, $rowId, 'error', $message);
			$this->logService->log(
				$definition->getBatchId(),
				'import',
				'error',
				$message,
				[],
				$rowNumber,
				null
			);
			return 'failed';
		}
	}

	/**
	 * Create new CRM record.
	 *
	 * @param array<string, mixed> $values
	 * @return array{state:string, message:string, recordId:int|null, logStatus:string, action:string, statBucket:string}
	 */
	private function createRecord(string $moduleName, array $values, int $userId): array
	{
		$recordModel = Record::getCleanInstance($moduleName);
		foreach ($values as $fieldName => $value) {
			if ($value === null || $value === '') {
				continue;
			}
			$recordModel->set($fieldName, $value);
		}
		if (!$recordModel->get('assigned_user_id')) {
			$recordModel->set('assigned_user_id', $userId);
		}
		$recordModel->save();
		$recordId = $recordModel->getId();

		return [
			'state' => 'imported',
			'message' => 'Record created',
			'recordId' => $recordId,
			'logStatus' => 'success',
			'action' => 'created',
			'statBucket' => 'created',
		];
	}

	/**
	 * Update existing record respect strategy.
	 *
	 * @param array<string, mixed> $values
	 * @return array{state:string, message:string, recordId:int|null, logStatus:string, action:string, statBucket:string}
	 */
	private function handleDuplicate(
		int $recordId,
		ModuleModel $module,
		array $values,
		string $strategy
	): array {
		if ($strategy === 'skip') {
			return [
				'state' => 'skipped',
				'message' => 'Duplicate skipped',
				'recordId' => $recordId,
				'logStatus' => 'warning',
				'action' => 'skipped',
				'statBucket' => 'skipped',
			];
		}

		$recordModel = Record::getInstanceById($recordId, $module->getName());
		$recordModel->setFullForm(false);

		$fields = $module->getFields();
		foreach ($values as $fieldName => $value) {
			if (!isset($fields[$fieldName])) {
				continue;
			}
			if ($value === null || $value === '') {
				continue;
			}
			$currentValue = $recordModel->get($fieldName);
			if ($strategy === 'merge' && !$this->shouldMergeValue($fields[$fieldName], $currentValue, $value)) {
				continue;
			}
			if ($strategy === 'overwrite' && !$this->shouldOverwriteValue($value)) {
				continue;
			}
			if ($fields[$fieldName]->getFieldDataType() === 'multipicklist') {
				$value = $this->mergeMultipicklistValues($currentValue, $value, $strategy);
				if ($value === null) {
					continue;
				}
			}
			$recordModel->set($fieldName, $value);
		}

		$recordModel->save();

		return [
			'state' => 'imported',
			'message' => $strategy === 'overwrite' ? 'Record overwritten' : 'Record merged',
			'recordId' => $recordId,
			'logStatus' => 'success',
			'action' => $strategy,
			'statBucket' => 'updated',
		];
	}

	private function shouldOverwriteValue($value): bool
	{
		return !($value === null || $value === '');
	}

	private function shouldMergeValue(\App\Modules\Base\Models\Field $field, $currentValue, $incoming): bool
	{
		if ($field->getFieldDataType() === 'multipicklist') {
			return true;
		}
		return $currentValue === null || $currentValue === '' || $currentValue === '0';
	}

	private function mergeMultipicklistValues($currentValue, $incomingValue, string $strategy): ?string
	{
		if ($strategy === 'overwrite') {
			return $incomingValue;
		}
		$current = $currentValue ? explode(' |##| ', (string) $currentValue) : [];
		$incoming = $incomingValue ? explode(' |##| ', (string) $incomingValue) : [];
		$merged = array_values(array_unique(array_filter(array_merge($current, $incoming))));
		return $merged ? implode(' |##| ', $merged) : null;
	}

	/**
	 * Extract mapped values from staging row.
	 *
	 * @return array<string, mixed>
	 */
	private function extractValues(array $row, MappingDefinition $definition): array
	{
		$values = [];
		foreach ($definition->getMapping() as $map) {
			$fieldName = $map['field'];
			$column = $this->tables->getColumnName($fieldName);
			$raw = $row[$column] ?? null;
			if (is_string($raw) && $this->looksLikeJson($raw)) {
				$decoded = \App\Utils\Json::decode($raw, true);
				if ($decoded !== null) {
					$raw = $decoded;
				}
			}
			$values[$fieldName] = $raw;
		}
		return $values;
	}

	private function updateRowStatus(string $table, int $rowId, string $status, ?string $message = null): void
	{
		$data = ['validation_status' => $status];
		if ($message) {
			$data['error_payload'] = \App\Utils\Json::encode([['message' => $message]]);
		}
		$this->db->createCommand()->update($table, $data, ['id' => $rowId])->execute();
	}

	private function looksLikeJson(string $value): bool
	{
		$value = trim($value);
		return (str_starts_with($value, '{') && str_ends_with($value, '}'))
			|| (str_starts_with($value, '[') && str_ends_with($value, ']'));
	}
}

