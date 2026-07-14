<?php
/**
 * FreeCRM - Email templates for screening rejection reasons (Pan form, not wired to transition matrix).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260714_000003_screening_rejection_email_templates extends Migration
{
	private const MODULE = 'Candidates';
	private const SETYPE = 'EmailTemplates';
	private const MODENTITY_TABID = 112;

	private const FOOTER = "\n\n\$(dynamic : current_footer)\$\n\$(dynamic : candidates_unsubscribe_footer)\$";

	private const TEMPLATES = [
		[
			'sys_name' => 'kandydaci_odrzucenie_brak_doswiadczenia',
			'name' => 'Kandydaci - Odrzucenie screening: brak doświadczenia',
			'body' => "Dzień dobry Panu,\n\ndziękujemy za zainteresowanie stanowiskiem \$(record : nazwa_projektu)\$ i przesłanie aplikacji.\n\nPo analizie przekazanych informacji nie możemy kontynuować procesu rekrutacyjnego — na tym etapie nie potwierdza Pan doświadczenia wymaganego na podobnym stanowisku.",
		],
		[
			'sys_name' => 'kandydaci_odrzucenie_brak_kompetencji',
			'name' => 'Kandydaci - Odrzucenie screening: brak kompetencji',
			'body' => "Dzień dobry Panu,\n\ndziękujemy za zainteresowanie stanowiskiem \$(record : nazwa_projektu)\$ i przesłanie aplikacji.\n\nPo analizie przekazanych informacji nie możemy kontynuować procesu rekrutacyjnego — w profilu nie widać kluczowych kompetencji, technologii, narzędzi lub certyfikatów wymaganych w tym projekcie.",
		],
		[
			'sys_name' => 'kandydaci_odrzucenie_niedopasowanie_profilu',
			'name' => 'Kandydaci - Odrzucenie screening: niedopasowanie profilu',
			'body' => "Dzień dobry Panu,\n\ndziękujemy za zainteresowanie stanowiskiem \$(record : nazwa_projektu)\$ i przesłanie aplikacji.\n\nPo analizie przekazanych informacji nie możemy kontynuować procesu rekrutacyjnego — profil zawodowy nie jest wystarczająco dopasowany do zakresu obowiązków w tym projekcie.",
		],
		[
			'sys_name' => 'kandydaci_odrzucenie_brak_jezyka_polskiego',
			'name' => 'Kandydaci - Odrzucenie screening: brak języka polskiego',
			'body' => "Dzień dobry Panu,\n\ndziękujemy za zainteresowanie stanowiskiem \$(record : nazwa_projektu)\$ i przesłanie aplikacji.\n\nPo analizie przekazanych informacji nie możemy kontynuować procesu rekrutacyjnego — nie potwierdza Pan wymaganej znajomości języka polskiego.",
		],
		[
			'sys_name' => 'kandydaci_odrzucenie_inny_kandydat',
			'name' => 'Kandydaci - Odrzucenie screening: inny kandydat',
			'body' => "Dzień dobry Panu,\n\ndziękujemy za zainteresowanie stanowiskiem \$(record : nazwa_projektu)\$ i przesłanie aplikacji.\n\nPo analizie przekazanych informacji nie możemy kontynuować procesu rekrutacyjnego w tym projekcie — wybraliśmy innego kandydata.",
		],
		[
			'sys_name' => 'kandydaci_odrzucenie_proces_zamkniety',
			'name' => 'Kandydaci - Odrzucenie screening: proces zamknięty',
			'body' => "Dzień dobry Panu,\n\ndziękujemy za zainteresowanie stanowiskiem \$(record : nazwa_projektu)\$ i przesłanie aplikacji.\n\nNie możemy kontynuować procesu rekrutacyjnego — rekrutacja na to stanowisko została wstrzymana lub zamknięta.",
		],
	];

	public function safeUp(): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}

		$subject = 'Informacja w sprawie rekrutacji na – $(record : nazwa_projektu)$';
		$now = date('Y-m-d H:i:s');

		foreach (self::TEMPLATES as $template) {
			$sysName = (string) $template['sys_name'];
			if ((new Query())
				->from('u_yf_emailtemplates')
				->where(['sys_name' => $sysName])
				->exists($this->db)) {
				echo "Skip existing template {$sysName}.\n";
				continue;
			}

			$this->insert('vtiger_crmentity', [
				'smcreatorid' => 1,
				'smownerid' => 1,
				'shownerid' => 0,
				'modifiedby' => 1,
				'setype' => self::SETYPE,
				'description' => null,
				'createdtime' => $now,
				'modifiedtime' => $now,
				'presence' => 1,
				'deleted' => 0,
				'was_read' => 0,
				'private' => 0,
			]);
			$id = (int) $this->db->getLastInsertID();
			$number = $this->allocateTemplateNumber();

			$this->insert('u_yf_emailtemplates', [
				'emailtemplatesid' => $id,
				'name' => (string) $template['name'],
				'number' => $number,
				'email_template_type' => 'PLL_RECORD',
				'modules' => self::MODULE,
				'subject' => $subject,
				'content' => (string) $template['body'] . self::FOOTER,
				'sys_name' => $sysName,
				'is_system' => 0,
				'email_template_priority' => 1,
				'sequence' => 2,
				'sender_type' => 'user_account',
			]);

			echo "Created EmailTemplate {$sysName} (id={$id}, {$number}).\n";
		}

		\App\Email\Mail::clearTemplateListCache();
	}

	public function safeDown(): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}

		foreach (self::TEMPLATES as $template) {
			$sysName = (string) $template['sys_name'];
			$id = (new Query())
				->select(['emailtemplatesid'])
				->from('u_yf_emailtemplates')
				->where(['sys_name' => $sysName])
				->scalar($this->db);
			if (!$id) {
				continue;
			}
			$id = (int) $id;
			$this->delete('u_yf_emailtemplates', ['emailtemplatesid' => $id]);
			$this->delete('vtiger_crmentity', ['crmid' => $id]);
			echo "Removed EmailTemplate {$sysName} (id={$id}).\n";
		}

		\App\Email\Mail::clearTemplateListCache();
	}

	private function allocateTemplateNumber(): string
	{
		$row = (new Query())
			->select(['cur_id'])
			->from('vtiger_modentity_num')
			->where(['tabid' => self::MODENTITY_TABID])
			->one($this->db);

		$next = (int) ($row['cur_id'] ?? 0) + 1;
		$this->update('vtiger_modentity_num', ['cur_id' => $next], ['tabid' => self::MODENTITY_TABID]);

		return 'N' . $next;
	}
}
