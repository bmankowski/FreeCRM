<?php
/**
 * FreeCRM - Widen ServiceContracts unit counters to match sum_time scale.
 *
 * total_units/used_units were decimal(5,2) — max 999.99 — which rejects values
 * like 1000 hours on contract save.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260630_000005_servicecontracts_units_decimal extends Migration
{
	private const TABLE = 'vtiger_servicecontracts';

	public function safeUp(): void
	{
		$this->alterColumn(self::TABLE, 'total_units', $this->decimal(10, 2)->null());
		$this->alterColumn(self::TABLE, 'used_units', $this->decimal(10, 2)->null());
	}

	public function safeDown(): void
	{
		$this->alterColumn(self::TABLE, 'total_units', $this->decimal(5, 2)->null());
		$this->alterColumn(self::TABLE, 'used_units', $this->decimal(5, 2)->null());
	}
}
