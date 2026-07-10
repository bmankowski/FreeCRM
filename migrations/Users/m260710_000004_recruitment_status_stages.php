<?php
/**
 * FreeCRM - Seed recruitment transitions for PPL_STAGE_1/2/3 when transition matrix is configured.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260710_000004_recruitment_status_stages extends Migration
{
	public function safeUp(): void
	{
		$configured = (int) (new Query())
			->select(['configured'])
			->from('u_yf_recruitment_settings')
			->where(['id' => 1])
			->scalar();

		if ($configured !== 1) {
			return;
		}

		$pairs = [
			['from_status' => 'PPL_CANDIDATE_PASSED_SCREENING', 'to_status' => 'PPL_STAGE_1'],
			['from_status' => 'PPL_STAGE_1', 'to_status' => 'PPL_STAGE_2'],
			['from_status' => 'PPL_STAGE_1', 'to_status' => 'PPL_REJECTED_AFTER_INTERVIEW'],
			['from_status' => 'PPL_STAGE_2', 'to_status' => 'PPL_STAGE_3'],
			['from_status' => 'PPL_STAGE_2', 'to_status' => 'PPL_REJECTED_AFTER_INTERVIEW'],
			['from_status' => 'PPL_STAGE_3', 'to_status' => 'PPL_HANDED_TO_SALES'],
			['from_status' => 'PPL_STAGE_3', 'to_status' => 'PPL_TO_BE_SENT_TO_CLIENT'],
			['from_status' => 'PPL_STAGE_3', 'to_status' => 'PPL_REJECTED_AFTER_INTERVIEW'],
		];

		foreach ($pairs as $pair) {
			$exists = (new Query())
				->from('u_yf_recruitment_status_transitions')
				->where($pair)
				->exists();
			if (!$exists) {
				$this->db->createCommand()->insert('u_yf_recruitment_status_transitions', $pair)->execute();
			}
		}
	}

	public function safeDown(): void
	{
		$this->db->createCommand()->delete('u_yf_recruitment_status_transitions', [
			'from_status' => 'PPL_STAGE_1',
		])->execute();
		$this->db->createCommand()->delete('u_yf_recruitment_status_transitions', [
			'from_status' => 'PPL_STAGE_2',
		])->execute();
		$this->db->createCommand()->delete('u_yf_recruitment_status_transitions', [
			'from_status' => 'PPL_STAGE_3',
		])->execute();
		$this->db->createCommand()->delete('u_yf_recruitment_status_transitions', [
			'from_status' => 'PPL_CANDIDATE_PASSED_SCREENING',
			'to_status' => 'PPL_STAGE_1',
		])->execute();
	}
}
