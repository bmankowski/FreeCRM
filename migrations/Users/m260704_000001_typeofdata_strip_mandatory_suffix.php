<?php
/**
 * Strip remaining ~M / ~O mandatory suffixes from vtiger_field.typeofdata.
 * The mandatory column is the sole authority; typeofdata must be a single type token.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260704_000001_typeofdata_strip_mandatory_suffix extends Migration
{
	private const AFFECTED_TABIDS = [8, 13, 112];

	public function safeUp(): void
	{
		$this->db->createCommand("
			UPDATE vtiger_field
			SET typeofdata = SUBSTRING_INDEX(typeofdata, '~', 1)
			WHERE typeofdata REGEXP '^[A-Z]+~(M|O)$'
		")->execute();

		$remaining = (int) $this->db->createCommand(
			"SELECT COUNT(*) FROM vtiger_field WHERE typeofdata REGEXP '~'"
		)->queryScalar();

		if ($remaining !== 0) {
			throw new \RuntimeException(
				"m260704_000001: expected 0 typeofdata rows with '~' after cleanup, found {$remaining}"
			);
		}

		foreach (self::AFFECTED_TABIDS as $tabid) {
			\App\Cache\Cache::delete('ModuleFields', (string) $tabid);
			\App\Cache\Cache::delete('fieldInfo', (string) $tabid);
		}
	}

	public function safeDown(): void
	{
		echo "m260704_000001: safeDown not supported — restore DB backup.\n";
	}
}
