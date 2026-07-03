<?php
/**
 * ProjektyRekrutacyjne: etap_sprzedazy uitype 16 (multipicklist) → 15 (picklist).
 *
 * Data is already single-value strings; no column migration needed.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260706_000001_projektyrekrutacyjne_etap_sprzedazy_picklist extends Migration
{
	private const TABID = 119;
	private const FIELD_ID = 2940;
	private const FIELD_NAME = 'etap_sprzedazy';

	public function safeUp(): void
	{
		if (!(new Query())->from('vtiger_field')->where(['fieldid' => self::FIELD_ID])->exists()) {
			echo "Field " . self::FIELD_NAME . " not found — skipping.\n";

			return;
		}

		$updates = ['uitype' => 15];
		$schema = $this->db->getTableSchema('vtiger_field', true);
		if ($schema !== null && isset($schema->columns['field_kind'])) {
			$updates['field_kind'] = 'picklist';
		}

		$this->update('vtiger_field', $updates, [
			'fieldid' => self::FIELD_ID,
			'tabid' => self::TABID,
		]);

		$this->clearFieldCache();
	}

	public function safeDown(): void
	{
		if (!(new Query())->from('vtiger_field')->where(['fieldid' => self::FIELD_ID])->exists()) {
			return;
		}

		$updates = ['uitype' => 16];
		$schema = $this->db->getTableSchema('vtiger_field', true);
		if ($schema !== null && isset($schema->columns['field_kind'])) {
			$updates['field_kind'] = 'multipicklist';
		}

		$this->update('vtiger_field', $updates, [
			'fieldid' => self::FIELD_ID,
			'tabid' => self::TABID,
		]);

		$this->clearFieldCache();
	}

	private function clearFieldCache(): void
	{
		$tabid = (string) self::TABID;
		\App\Cache\Cache::delete('ModuleFields', $tabid);
		\App\Cache\Cache::delete('fieldInfo', $tabid);
		\App\Cache\Cache::delete('field-' . $tabid, self::FIELD_ID);
		\App\Cache\Cache::delete('field-' . $tabid, self::FIELD_NAME);
	}
}
