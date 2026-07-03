<?php
/**
 * ProjektyRekrutacyjne: remaining uitype-16 fields → picklist (uitype 15).
 *
 * Fields: priorytet, rodzaj, tryb_pracy, zrodlo_pozyskania_projektu.
 * Data is already single-value strings; no column migration needed.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260706_000002_projektyrekrutacyjne_picklist_fields extends Migration
{
	private const TABID = 119;

	/** @var array<int, string> fieldid => fieldname */
	private const FIELDS = [
		2939 => 'rodzaj',
		2941 => 'priorytet',
		2947 => 'tryb_pracy',
		2964 => 'zrodlo_pozyskania_projektu',
	];

	public function safeUp(): void
	{
		$this->applyUitype(15, 'picklist');
	}

	public function safeDown(): void
	{
		$this->applyUitype(16, 'multipicklist');
	}

	private function applyUitype(int $uitype, string $fieldKind): void
	{
		$updates = ['uitype' => $uitype];
		$schema = $this->db->getTableSchema('vtiger_field', true);
		if ($schema !== null && isset($schema->columns['field_kind'])) {
			$updates['field_kind'] = $fieldKind;
		}

		foreach (self::FIELDS as $fieldId => $fieldName) {
			if (!(new Query())->from('vtiger_field')->where(['fieldid' => $fieldId])->exists()) {
				echo "Field {$fieldName} ({$fieldId}) not found — skipping.\n";
				continue;
			}

			$this->update('vtiger_field', $updates, [
				'fieldid' => $fieldId,
				'tabid' => self::TABID,
			]);
		}

		$this->clearFieldCache();
	}

	private function clearFieldCache(): void
	{
		$tabid = (string) self::TABID;
		\App\Cache\Cache::delete('ModuleFields', $tabid);
		\App\Cache\Cache::delete('fieldInfo', $tabid);

		foreach (self::FIELDS as $fieldId => $fieldName) {
			\App\Cache\Cache::delete('field-' . $tabid, $fieldId);
			\App\Cache\Cache::delete('field-' . $tabid, $fieldName);
		}
	}
}
