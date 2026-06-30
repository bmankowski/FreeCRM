<?php
/**
 * FreeCRM - Enable record autonumbering for ProjektyRekrutacyjne (POT prefix, zero-padded).
 *
 * The module has a uitype 4 field (`number`) but no vtiger_modentity_num row, so new
 * projects were saved without a number. Sequence width is derived from existing values
 * (POT00001 .. POT02050 → 5 digits) and stored padded; columns are varchar so the
 * leading zeros are preserved.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260630_000004_projektyrekrutacyjne_record_numbering extends Migration
{
	private const TABID = 119;
	private const PREFIX = 'POT';
	private const TABLE = 'u_yf_projektyrekrutacyjne';
	private const INDEX = 'projektyrekrutacyjneid';

	public function safeUp(): void
	{
		$maxSeq = (int) (new Query())
			->select(['MAX(CAST(REGEXP_REPLACE(number, \'[^0-9]\', \'\') AS UNSIGNED))'])
			->from(self::TABLE)
			->where(['REGEXP', 'number', '^' . self::PREFIX . '[0-9]+$'])
			->scalar($this->db);

		$width = (int) (new Query())
			->select(['MAX(CHAR_LENGTH(REGEXP_REPLACE(number, \'[^0-9]\', \'\')))'])
			->from(self::TABLE)
			->where(['REGEXP', 'number', '^' . self::PREFIX . '[0-9]+$'])
			->scalar($this->db);
		$width = max(1, $width);

		$nextSeq = max(1, $maxSeq + 1);

		if (!(new Query())->from('vtiger_modentity_num')->where(['tabid' => self::TABID])->exists()) {
			$this->insert('vtiger_modentity_num', [
				'tabid' => self::TABID,
				'prefix' => self::PREFIX,
				'postfix' => '',
				'start_id' => str_pad((string) $nextSeq, $width, '0', STR_PAD_LEFT),
				'cur_id' => str_pad((string) $nextSeq, $width, '0', STR_PAD_LEFT),
			]);
		}

		$emptyIds = (new Query())
			->select(['p.' . self::INDEX])
			->from(['p' => self::TABLE])
			->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = p.' . self::INDEX)
			->where(['e.deleted' => 0])
			->andWhere(['or', ['p.number' => null], ['p.number' => '']])
			->orderBy(['p.' . self::INDEX => SORT_ASC])
			->column($this->db);

		foreach ($emptyIds as $recordId) {
			$number = self::PREFIX . str_pad((string) $nextSeq, $width, '0', STR_PAD_LEFT);
			$this->update(self::TABLE, ['number' => $number], [self::INDEX => (int) $recordId]);
			$nextSeq++;
		}

		if ($emptyIds !== []) {
			$this->update('vtiger_modentity_num', [
				'cur_id' => str_pad((string) $nextSeq, $width, '0', STR_PAD_LEFT),
			], ['tabid' => self::TABID]);
		}
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_modentity_num', ['tabid' => self::TABID]);
	}
}
