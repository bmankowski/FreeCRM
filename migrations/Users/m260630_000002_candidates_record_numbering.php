<?php
/**
 * FreeCRM - Enable record autonumbering for Candidates (PRO prefix).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260630_000002_candidates_record_numbering extends Migration
{
	private const TABID = 121;
	private const PREFIX = 'PRO';

	public function safeUp(): void
	{
		$maxSeq = (int) (new Query())
			->select(['MAX(CAST(REGEXP_REPLACE(number, \'[^0-9]\', \'\') AS UNSIGNED))'])
			->from('u_yf_candidates')
			->where(['not', ['number' => null]])
			->andWhere(['<>', 'number', ''])
			->scalar($this->db);

		$nextId = max(1, $maxSeq + 1);

		if (!(new Query())->from('vtiger_modentity_num')->where(['tabid' => self::TABID])->exists()) {
			$this->insert('vtiger_modentity_num', [
				'tabid' => self::TABID,
				'prefix' => self::PREFIX,
				'postfix' => '',
				'start_id' => $nextId,
				'cur_id' => $nextId,
			]);
		}

		$emptyIds = (new Query())
			->select(['c.candidatesid'])
			->from(['c' => 'u_yf_candidates'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = c.candidatesid')
			->where(['e.deleted' => 0])
			->andWhere(['or', ['c.number' => null], ['c.number' => '']])
			->orderBy(['c.candidatesid' => SORT_ASC])
			->column($this->db);

		foreach ($emptyIds as $candidateId) {
			$number = self::PREFIX . $nextId;
			$this->update('u_yf_candidates', ['number' => $number], ['candidatesid' => (int) $candidateId]);
			$nextId++;
		}

		if ($emptyIds !== []) {
			$this->update('vtiger_modentity_num', ['cur_id' => $nextId], ['tabid' => self::TABID]);
		}
	}

	public function safeDown(): void
	{
		$this->delete('vtiger_modentity_num', ['tabid' => self::TABID]);
	}
}
