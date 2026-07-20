<?php
/**
 * FreeCRM - Application-received template: nazwa_projektu from sourceRecord (project), not record (candidate).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260720_000005_application_received_source_record_placeholders extends Migration
{
	private const SYS_NAME = 'kandydaci_potwierdzenie_otrzymania_aplikacji';

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

		$id = (int) (new Query())
			->select(['emailtemplatesid'])
			->from('u_yf_emailtemplates')
			->where(['sys_name' => self::SYS_NAME])
			->scalar($this->db);
		if ($id <= 0) {
			echo 'Skip: template ' . self::SYS_NAME . " not found.\n";

			return;
		}

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

		\App\Email\Mail::clearTemplateListCache();
	}
}
