<?php
/**
 * FreeCRM - Store record-numbering sequence as text so leading zeros are preserved.
 *
 * `vtiger_modentity_num.start_id` / `cur_id` were INT, which silently dropped leading
 * zeros. RecordNumber::incrementNumber() was already written to preserve sequence width
 * (str_repeat padding); it only failed because the column re-truncated the value.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260630_000003_record_numbering_leading_zeros extends Migration
{
	private const CANDIDATES_TABID = 121;

	public function safeUp(): void
	{
		$this->alterColumn('vtiger_modentity_num', 'start_id', $this->string(50)->notNull()->defaultValue('0'));
		$this->alterColumn('vtiger_modentity_num', 'cur_id', $this->string(50)->notNull()->defaultValue('0'));

		$this->repadCandidates();
	}

	public function safeDown(): void
	{
		$this->alterColumn('vtiger_modentity_num', 'start_id', $this->integer()->unsigned()->notNull());
		$this->alterColumn('vtiger_modentity_num', 'cur_id', $this->integer()->unsigned()->notNull());
	}

	private function repadCandidates(): void
	{
		$row = (new Query())
			->select(['start_id', 'cur_id'])
			->from('vtiger_modentity_num')
			->where(['tabid' => self::CANDIDATES_TABID])
			->one($this->db);
		if ($row === false) {
			return;
		}

		$width = (int) (new Query())
			->select(['MAX(CHAR_LENGTH(REGEXP_REPLACE(number, \'[^0-9]\', \'\')))'])
			->from('u_yf_candidates')
			->where(['REGEXP', 'number', '^PRO[0-9]+$'])
			->scalar($this->db);
		if ($width <= 0) {
			return;
		}

		$this->update('vtiger_modentity_num', [
			'start_id' => str_pad((string) (int) $row['start_id'], $width, '0', STR_PAD_LEFT),
			'cur_id' => str_pad((string) (int) $row['cur_id'], $width, '0', STR_PAD_LEFT),
		], ['tabid' => self::CANDIDATES_TABID]);
	}
}
