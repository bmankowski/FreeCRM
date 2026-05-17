<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Loads mapped rows into staging tables.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;
use App\Modules\ImportManager\Parsers\ParserFactory;

class StagingWriter
{
	private ConfigProvider $config;
	private TemporaryTableManager $tables;
	private ParserFactory $parserFactory;
	private RecordValidator $validator;
	private \yii\db\Connection $db;

	public function __construct(
		?ConfigProvider $config = null,
		?TemporaryTableManager $tables = null,
		?ParserFactory $parserFactory = null,
		?RecordValidator $validator = null,
		?\yii\db\Connection $db = null
	) {
		$this->config = $config ?? new ConfigProvider();
		$this->tables = $tables ?? new TemporaryTableManager();
		$this->parserFactory = $parserFactory ?? new ParserFactory($this->config);
		$this->validator = $validator ?? new RecordValidator();
		$this->db = $db ?? \App\Db\Db::getInstance();
	}

	/**
	 * @param array $batch batch row from DB
	 */
	public function stage(array $batch, ModuleModel $module, MappingDefinition $definition): array
	{
		$tableInfo = $this->tables->recreate($module, $definition->getBatchId());
		$fieldMapper = new FieldMapper($module, $definition);

		$parser = $this->parserFactory->create(
			$batch['format'],
			ROOT_DIRECTORY . '/' . ltrim($batch['file_path'], '/'),
			[
				'delimiter' => $batch['delimiter'] ?? null,
				'enclosure' => $batch['enclosure'] ?? null,
				'encoding' => $batch['encoding'] ?? null,
				'xpath' => $batch['xpath'] ?? null,
			]
		);

		$chunkSize = $this->config->getChunkSize();
		$chunk = [];
		$total = 0;
		$failed = 0;

		$currentUserId = (int) (\App\User\CurrentUser::getId() ?? 0);
		
		$parser->iterate(function (array $sourceRow) use (&$chunk, $chunkSize, &$total, &$failed, $fieldMapper, $tableInfo, $module, $definition, $currentUserId) {
			$total++;
			$rowNumber = $total;
			$values = $fieldMapper->mapRow($sourceRow);
			
			// Auto-fill assigned_user_id with current user if empty
			if ($this->isFieldEmpty($values, 'assigned_user_id')) {
				$values['assigned_user_id'] = $currentUserId;
			}
			
			$validation = $this->validator->validate($module, $values, $definition);
			if ($validation['status'] === RecordValidator::STATUS_FAILED) {
				$failed++;
			}

			$chunk[] = $this->buildInsertPayload(
				$tableInfo['columns'],
				$values,
				$rowNumber,
				$definition->getBatchId(),
				$validation
			);

			if (count($chunk) >= $chunkSize) {
				$this->flushChunk($tableInfo['table'], $chunk);
				$chunk = [];
			}
		});

		if ($chunk) {
			$this->flushChunk($tableInfo['table'], $chunk);
		}

		return [
			'table' => $tableInfo['table'],
			'total' => $total,
			'failed' => $failed,
		];
	}

	private function buildInsertPayload(array $columnMap, array $values, int $rowNumber, int $batchId, array $validation): array
	{
		$row = [
			'batch_id' => $batchId,
			'row_number' => $rowNumber,
			'row_hash' => hash('sha256', json_encode($values)),
			'validation_status' => $validation['status'],
			'error_payload' => $validation['errors'] ? \App\Utils\Json::encode($validation['errors']) : null,
			'retry_token' => null,
		];

		foreach ($columnMap as $fieldName => $columnName) {
			$value = $values[$fieldName] ?? null;
			if (is_array($value)) {
				$value = \App\Utils\Json::encode($value);
			}
			$row[$columnName] = $value;
		}

		return $row;
	}

	private function flushChunk(string $tableName, array $rows): void
	{
		if (!$rows) {
			return;
		}
		$columns = array_keys($rows[0]);
		$values = array_map(static function ($row) use ($columns) {
			return array_map(static fn($column) => $row[$column] ?? null, $columns);
		}, $rows);

		$this->db->createCommand()
			->batchInsert($tableName, $columns, $values)
			->execute();
	}

	/**
	 * Check if a field value is empty or not set.
	 */
	private function isFieldEmpty(array $values, string $fieldName): bool
	{
		if (!array_key_exists($fieldName, $values)) {
			return true;
		}
		$value = $values[$fieldName];
		return $value === null || $value === '' || (is_string($value) && trim($value) === '');
	}
}

