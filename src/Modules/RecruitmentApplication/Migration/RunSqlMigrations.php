<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\RecruitmentApplication\Migration;

final class RunSqlMigrations
{
	public static function execute(): void
	{
		$db = \App\Db\Db::getInstance();
		$sqlDir = dirname(__DIR__) . '/sql';

		$duplicates = (new \App\Db\Query())
			->select(['application_number', 'cnt' => 'COUNT(*)'])
			->from('vtiger_recruitmentapplication')
			->where(['not', ['application_number' => null]])
			->andWhere(['!=', 'application_number', ''])
			->groupBy('application_number')
			->having(['>', 'cnt', 1])
			->all();
		if ($duplicates !== []) {
			throw new \RuntimeException('Duplicate application_number values block UNIQUE index migration.');
		}

		self::runFile($db, $sqlDir . '/001_bootstrap_module.sql');
		self::runFile($db, $sqlDir . '/002_cleanup_field_metadata.sql');

		$schema = $db->createCommand('SELECT DATABASE()')->queryScalar();
		$indexExists = (new \App\Db\Query())
			->from('information_schema.statistics')
			->where([
				'table_schema' => $schema,
				'table_name' => 'vtiger_recruitmentapplication',
				'index_name' => 'uq_recruitmentapplication_application_number',
			])
			->exists();
		if (!$indexExists) {
			self::runFile($db, $sqlDir . '/003_unique_application_number.sql');
		}

		echo "RecruitmentApplication SQL migrations completed.\n";
	}

	private static function runFile(\yii\db\Connection $db, string $path): void
	{
		if (!is_readable($path)) {
			throw new \RuntimeException("Migration file not readable: {$path}");
		}
		$sql = file_get_contents($path);
		foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
			if ($statement === '' || str_starts_with($statement, '--')) {
				continue;
			}
			$db->createCommand($statement)->execute();
		}
		echo "Executed: " . basename($path) . "\n";
	}
}
