<?php
/**
 * FreeCRM - Seed recruitment transitions for PPL_HANDED_TO_SALES when transition matrix is configured.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260710_000003_recruitment_status_handed_to_sales extends Migration
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
			['from_status' => 'PPL_WAITING_FOR_INTERVIEW', 'to_status' => 'PPL_HANDED_TO_SALES'],
			['from_status' => 'PPL_HANDED_TO_SALES', 'to_status' => 'PPL_TO_BE_SENT_TO_CLIENT'],
			['from_status' => 'PPL_HANDED_TO_SALES', 'to_status' => 'PPL_SENT_TO_CLIENT'],
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
			'from_status' => 'PPL_HANDED_TO_SALES',
		])->execute();
		$this->db->createCommand()->delete('u_yf_recruitment_status_transitions', [
			'from_status' => 'PPL_WAITING_FOR_INTERVIEW',
			'to_status' => 'PPL_HANDED_TO_SALES',
		])->execute();
	}
}
