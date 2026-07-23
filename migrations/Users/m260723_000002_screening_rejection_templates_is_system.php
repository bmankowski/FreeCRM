<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Hide screening-rejection Candidates templates from manual compose (is_system=1).
 * Event path still resolves them by sys_name via ScreeningRejectionMail.
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

class m260723_000002_screening_rejection_templates_is_system extends Migration
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

	public function safeUp(): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}

		$this->update(
			'u_yf_emailtemplates',
			['is_system' => 1],
			['sys_name' => self::SYS_NAMES]
		);
		\App\Email\Mail::clearTemplateListCache();
	}

	public function safeDown(): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}

		$this->update(
			'u_yf_emailtemplates',
			['is_system' => 0],
			['sys_name' => self::SYS_NAMES]
		);
		\App\Email\Mail::clearTemplateListCache();
	}
}
