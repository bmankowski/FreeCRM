<?php
/**
 * FreeCRM - Recruitment templates: user picks sender; smtp_id stays default only.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260606_000001_recruitment_emailtemplates_sender_any extends Migration
{
	public function safeUp(): void
	{
		$this->update(
			'u_yf_emailtemplates',
			['sender_type' => 'any'],
			[
				'and',
				['module' => ['Kandydaci', 'ProjektyRekrutacyjne']],
				['sys_name' => null],
			]
		);
	}

	public function safeDown(): void
	{
		$this->update(
			'u_yf_emailtemplates',
			['sender_type' => 'system_smtp'],
			[
				'and',
				['module' => ['Kandydaci', 'ProjektyRekrutacyjne']],
				['sys_name' => null],
			]
		);
	}
}
