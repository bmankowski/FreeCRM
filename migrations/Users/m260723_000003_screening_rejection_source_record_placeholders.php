<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Screening-rejection templates: nazwa_projektu from sourceRecord (project), not record (candidate).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260723_000003_screening_rejection_source_record_placeholders extends Migration
{
	/** @var list<string> */
	private const SYS_NAMES = [
		'kandydaci_odrzucenie_brak_doswiadczenia',
		'kandydaci_odrzucenie_brak_kompetencji',
		'kandydaci_odrzucenie_niedopasowanie_profilu',
		'kandydaci_odrzucenie_brak_jezyka_polskiego',
		'kandydaci_odrzucenie_inny_kandydat',
		'kandydaci_odrzucenie_proces_zamkniety',
	];

	private const FROM = '$(record : nazwa_projektu)$';

	private const TO = '$(sourceRecord : nazwa_projektu)$';

	public function safeUp(): void
	{
		$this->rewritePlaceholders(self::FROM, self::TO);
	}

	public function safeDown(): void
	{
		$this->rewritePlaceholders(self::TO, self::FROM);
	}

	private function rewritePlaceholders(string $from, string $to): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}

		$ids = (new Query())
			->select(['emailtemplatesid'])
			->from('u_yf_emailtemplates')
			->where(['sys_name' => self::SYS_NAMES])
			->column($this->db);
		if ($ids === []) {
			echo "Skip: no screening-rejection templates found.\n";

			return;
		}

		foreach ($ids as $id) {
			$id = (int) $id;
			foreach (['subject', 'content'] as $column) {
				$sql = 'UPDATE `u_yf_emailtemplates` SET `' . $column . '` = REPLACE(`' . $column . '`, :from, :to)'
					. ' WHERE `emailtemplatesid` = :id AND `' . $column . '` LIKE :like';
				$n = $this->db->createCommand($sql, [
					':from' => $from,
					':to' => $to,
					':id' => $id,
					':like' => '%' . $from . '%',
				])->execute();
				if ($n > 0) {
					echo "Updated {$column} on EmailTemplate {$id} ({$from} → {$to}).\n";
				}
			}
		}

		\App\Email\Mail::clearTemplateListCache();
	}
}
