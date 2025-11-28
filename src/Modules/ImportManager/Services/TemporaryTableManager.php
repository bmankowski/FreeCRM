<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Manages staging tables for ImportManager batches.
 */

declare(strict_types=1);

namespace App\Modules\ImportManager\Services;

use App\Modules\Base\Models\Module as ModuleModel;

class TemporaryTableManager
{
	private \yii\db\Connection $db;
	private ConfigProvider $config;

	public function __construct(?\yii\db\Connection $db = null, ?ConfigProvider $config = null)
	{
		$this->db = $db ?? \App\Db\Db::getInstance();
		$this->config = $config ?? new ConfigProvider();
	}

	/**
	 * Creates (or recreates) staging table for batch and returns table metadata.
	 *
	 * @return array{table:string, columns:array<string,string>}
	 */
	public function recreate(ModuleModel $module, int $batchId): array
	{
		$tableName = $this->getTableName($module->getName(), $batchId);
		$this->drop($tableName);

		$fieldColumns = $this->buildFieldColumnMap($module);
		$sql = $this->buildCreateStatement($tableName, $fieldColumns);
		$this->db->createCommand($sql)->execute();

		return [
			'table' => $tableName,
			'columns' => $fieldColumns,
		];
	}

	public function getTableName(string $moduleName, int $batchId): string
	{
		$prefix = $this->config->get('staging.tablePrefix', 'import_stage_');
		return $prefix . strtolower($moduleName) . '_' . $batchId;
	}

	public function getColumnName(string $fieldName): string
	{
		return 'f_' . strtolower($fieldName);
	}

	public function drop(string $tableName): void
	{
		if ($this->tableExists($tableName)) {
			$this->db->createCommand()->dropTable($tableName)->execute();
		}
	}

	private function tableExists(string $tableName): bool
	{
		return (bool) $this->db->getTableSchema($tableName, true);
	}

	private function buildFieldColumnMap(ModuleModel $module): array
	{
		$columns = [];
		foreach ($module->getFields() as $fieldModel) {
			if (!$fieldModel->isActiveField()) {
				continue;
			}
			$columns[$fieldModel->getName()] = $this->getColumnName($fieldModel->getName());
		}
		return $columns;
	}

	private function buildCreateStatement(string $tableName, array $fieldColumns): string
	{
		$columnsSql = [
			'`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT',
			'`batch_id` INT NOT NULL',
			'`row_number` INT NOT NULL',
			'`row_hash` CHAR(64) NOT NULL',
			'`validation_status` VARCHAR(20) NOT NULL DEFAULT \'pending\'',
			'`error_payload` MEDIUMTEXT NULL',
			'`retry_token` CHAR(64) NULL',
		];

		foreach ($fieldColumns as $columnName) {
			$columnsSql[] = sprintf('`%s` MEDIUMTEXT NULL', $columnName);
		}

		$columnsSql[] = 'PRIMARY KEY (`id`)';
		$columnsSql[] = 'KEY `idx_stage_batch` (`batch_id`)';
		$columnsSql[] = 'KEY `idx_stage_status` (`validation_status`)';

		return sprintf(
			'CREATE TABLE `%s` (%s) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
			$tableName,
			implode(',', $columnsSql)
		);
	}
}

