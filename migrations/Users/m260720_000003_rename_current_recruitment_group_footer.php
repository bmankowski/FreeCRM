<?php
/**
 * FreeCRM - Rename current_recruitment_footer → current_recruitment_group_footer.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260720_000003_rename_current_recruitment_group_footer extends Migration
{
	private const OLD_CODE = 'current_recruitment_footer';

	private const NEW_CODE = 'current_recruitment_group_footer';

	private const EMAIL_SYS_NAME = 'kandydaci_potwierdzenie_otrzymania_aplikacji';

	private const NEW_FOOTER = "$(dynamic : current_recruitment_group_footer)$\n$(dynamic : candidates_unsubscribe_footer)$";

	public function safeUp(): void
	{
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		$id = (int) (new Query())
			->select(['templateelementsid'])
			->from('u_yf_templateelements')
			->where(['code' => self::OLD_CODE])
			->scalar($this->db);

		if ($id > 0) {
			$this->update('u_yf_templateelements', [
				'code' => self::NEW_CODE,
				'label' => 'Current recruitment group footer',
				'description' => 'Alias stopki grupy Rekrutacja (jak current_user_footer → standard_user_footer).',
			], ['templateelementsid' => $id]);
			$this->update('vtiger_crmentity', [
				'description' => self::NEW_CODE,
			], ['crmid' => $id, 'setype' => 'TemplateElements']);
			echo "Renamed TemplateElement {$id}: " . self::OLD_CODE . ' → ' . self::NEW_CODE . ".\n";
		} elseif (!(new Query())->from('u_yf_templateelements')->where(['code' => self::NEW_CODE])->exists($this->db)) {
			echo 'Skip rename: neither ' . self::OLD_CODE . ' nor ' . self::NEW_CODE . " found.\n";
		}

		if ($this->db->getTableSchema('u_yf_emailtemplates', true) !== null) {
			$this->update('u_yf_emailtemplates', [
				'footer' => self::NEW_FOOTER,
			], ['sys_name' => self::EMAIL_SYS_NAME]);
		}

		\App\Email\Mail::clearTemplateListCache();
	}

	public function safeDown(): void
	{
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		$id = (int) (new Query())
			->select(['templateelementsid'])
			->from('u_yf_templateelements')
			->where(['code' => self::NEW_CODE])
			->scalar($this->db);
		if ($id <= 0) {
			return;
		}

		$this->update('u_yf_templateelements', [
			'code' => self::OLD_CODE,
			'label' => 'Current recruitment footer',
		], ['templateelementsid' => $id]);
		$this->update('vtiger_crmentity', [
			'description' => self::OLD_CODE,
		], ['crmid' => $id, 'setype' => 'TemplateElements']);

		if ($this->db->getTableSchema('u_yf_emailtemplates', true) !== null) {
			$this->update('u_yf_emailtemplates', [
				'footer' => "$(dynamic : current_recruitment_footer)$\n$(dynamic : candidates_unsubscribe_footer)$",
			], ['sys_name' => self::EMAIL_SYS_NAME]);
		}

		\App\Email\Mail::clearTemplateListCache();
	}
}
