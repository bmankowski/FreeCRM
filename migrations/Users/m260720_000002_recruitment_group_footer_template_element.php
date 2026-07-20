<?php
/**
 * FreeCRM - Recruitment group email footer TemplateElement + wire application-received template.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260720_000002_recruitment_group_footer_template_element extends Migration
{
	private const ELEMENT_CODE = 'recruitment_group_footer';

	private const ALIAS_CODE = 'current_recruitment_group_footer';

	private const EMAIL_SYS_NAME = 'kandydaci_potwierdzenie_otrzymania_aplikacji';

	private const FOOTER_FIELD = "$(dynamic : current_recruitment_group_footer)$\n$(dynamic : candidates_unsubscribe_footer)$";

	public function safeUp(): void
	{
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		$this->seedElement(
			self::ELEMENT_CODE,
			'Recruitment group footer',
			$this->footerHtml(),
			'Stopka grupy Rekrutacja (rekrutacja@itconnect.pl) — odpowiednik standard_user_footer bez danych usera.'
		);
		$this->seedElement(
			self::ALIAS_CODE,
			'Current recruitment group footer',
			'$(dynamic : ' . self::ELEMENT_CODE . ')$',
			'Alias stopki grupy Rekrutacja (jak current_user_footer → standard_user_footer).'
		);

		$this->wireApplicationReceivedTemplate();
		\App\Email\Mail::clearTemplateListCache();
	}

	public function safeDown(): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) !== null) {
			$this->update(
				'u_yf_emailtemplates',
				['footer' => null],
				['sys_name' => self::EMAIL_SYS_NAME]
			);
		}

		foreach ([self::ALIAS_CODE, self::ELEMENT_CODE] as $code) {
			$id = (int) (new Query())
				->select(['templateelementsid'])
				->from('u_yf_templateelements')
				->where(['code' => $code])
				->scalar($this->db);
			if ($id <= 0) {
				continue;
			}
			$this->delete('u_yf_templateelements', ['templateelementsid' => $id]);
			$this->delete('vtiger_crmentity', ['crmid' => $id, 'setype' => 'TemplateElements']);
		}

		\App\Email\Mail::clearTemplateListCache();
	}

	private function seedElement(string $code, string $label, string $content, string $description): void
	{
		if ((new Query())->from('u_yf_templateelements')->where(['code' => $code])->exists($this->db)) {
			echo "Skip existing TemplateElement {$code}.\n";

			return;
		}

		$id = $this->nextEntityId();
		$now = date('Y-m-d H:i:s');
		$this->insert('vtiger_crmentity', [
			'crmid' => $id,
			'smcreatorid' => 1,
			'smownerid' => 1,
			'shownerid' => 0,
			'modifiedby' => 1,
			'setype' => 'TemplateElements',
			'description' => $code,
			'createdtime' => $now,
			'modifiedtime' => $now,
			'presence' => 1,
			'deleted' => 0,
			'was_read' => 0,
			'private' => 0,
		]);
		$this->insert('u_yf_templateelements', [
			'templateelementsid' => $id,
			'code' => $code,
			'label' => $label,
			'type' => 'PLL_VARIABLE_ALIAS',
			'module_name' => '',
			'language' => '',
			'status' => 1,
			'sequence' => 0,
			'content' => $content,
			'layout_header' => '',
			'layout_body' => '',
			'layout_footer' => '',
			'description' => $description,
		]);
		$this->bumpCrmEntitySeq($id);
		echo "Created TemplateElement {$code} (id={$id}).\n";
	}

	private function wireApplicationReceivedTemplate(): void
	{
		if ($this->db->getTableSchema('u_yf_emailtemplates', true) === null) {
			return;
		}

		$id = (int) (new Query())
			->select(['emailtemplatesid'])
			->from('u_yf_emailtemplates')
			->where(['sys_name' => self::EMAIL_SYS_NAME])
			->scalar($this->db);
		if ($id <= 0) {
			echo 'Skip footer wire: email template ' . self::EMAIL_SYS_NAME . " not found.\n";

			return;
		}

		$this->update('u_yf_emailtemplates', [
			'footer' => self::FOOTER_FIELD,
		], ['emailtemplatesid' => $id]);
		echo "Wired footer on EmailTemplate " . self::EMAIL_SYS_NAME . " (id={$id}).\n";
	}

	private function nextEntityId(): int
	{
		$maxElement = (int) (new Query())->from('u_yf_templateelements')->max('templateelementsid', $this->db);
		$maxCrm = (int) (new Query())->from('vtiger_crmentity')->max('crmid', $this->db);

		return max($maxElement, $maxCrm) + 1;
	}

	private function bumpCrmEntitySeq(int $id): void
	{
		if ($this->db->getTableSchema('vtiger_crmentity_seq', true) === null) {
			return;
		}
		$current = (int) (new Query())->from('vtiger_crmentity_seq')->max('id', $this->db);
		if ($id > $current) {
			$this->update('vtiger_crmentity_seq', ['id' => $id]);
		}
	}

	private function footerHtml(): string
	{
		// Logo under greeting (not beside) — mailLogo is wide and overlaps text in email clients.
		return <<<'HTML'
  <p><br></p>

  <table border="0" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background-color:#ffffff;border-collapse:collapse;">
    <tr>
      <td style="font-family:Lato, Arial, Helvetica, sans-serif;vertical-align:top;">
        <p style="margin:0 0 4px 0;font-size:15px;line-height:1.35;font-weight:700;color:#8B7AB5;">Pozdrawiamy,</p>
        <p style="margin:0 0 6px 0;font-size:15px;line-height:1.25;font-weight:900;color:#632E8E;">Rekrutacja IT CONNECT</p>
        <p style="margin:0 0 8px 0;font-size:15px;line-height:1.35;font-weight:700;color:#F58220;">Zespół rekrutacyjny</p>
        <span style="display:inline-block;width:80px;max-width:80px;">$(organization : mailLogo)$</span>
      </td>
    </tr>

    <tr>
      <td style="padding:12px 0 0 0;">
        <table border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td style="width:20px;padding:0 8px 8px 0;vertical-align:top;">$(dynamic : icon_mail)$</td>
            <td style="padding:0 0 8px 0;font-size:14px;line-height:1.4;color:#632E8E;font-weight:700;font-family:Lato, Arial, Helvetica, sans-serif;">
              <a href="mailto:rekrutacja@itconnect.pl" style="color:#632E8E;text-decoration:underline;">rekrutacja@itconnect.pl</a>
            </td>
          </tr>
          <tr>
            <td style="width:20px;padding:0 8px 8px 0;vertical-align:top;">$(dynamic : icon_web)$</td>
            <td style="padding:0 0 8px 0;font-size:14px;line-height:1.4;font-family:Lato, Arial, Helvetica, sans-serif;">
              <a href="https://www.itconnect.pl/" style="color:#632E8E;text-decoration:underline;">www.itconnect.pl</a>
            </td>
          </tr>
          <tr>
            <td style="width:20px;padding:0 8px 0 0;vertical-align:top;">$(dynamic : icon_address)$</td>
            <td style="padding:0;font-size:14px;line-height:1.45;color:#632E8E;font-weight:700;font-family:Lato, Arial, Helvetica, sans-serif;">
              ul.&nbsp;Chłodna&nbsp;51, piętro&nbsp;22<br>00‑867&nbsp;Warszawa
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  <table border="0" cellpadding="0" cellspacing="0">
    <tr>
      <td style="vertical-align:middle;">
        <p style="margin:5px 0 0 0;font-size:13px;line-height:1.4;font-family:Lato, Arial, Helvetica, sans-serif;">
          <a href="https://www.itconnect.pl/polityka-prywatnosci/" style="color:#632E8E;text-decoration:underline;">Polityka prywatności</a>
        </p>
      </td>
      <td style="vertical-align:middle;padding:0 0 0 12px;white-space:nowrap;font-family:Lato, Arial, Helvetica, sans-serif;">
        <a href="https://www.facebook.com/ITCPeopleinIT/" style="text-decoration:none;display:inline-block;margin-left:4px;">$(dynamic : icon_facebook)$</a>
        <a href="https://www.instagram.com/itcpeopleinit/" style="text-decoration:none;display:inline-block;margin-left:4px;">$(dynamic : icon_instagram)$</a>
        <a href="https://pl.linkedin.com/company/it-connect-people-in-it" style="text-decoration:none;display:inline-block;margin-left:4px;">$(dynamic : icon_linkedin)$</a>
        <a href="https://www.youtube.com/@ITCONNECT-PeopleinIT" style="text-decoration:none;display:inline-block;margin-left:4px;">$(dynamic : icon_youtube)$</a>
        <a href="https://www.tiktok.com/@itconnect2007" style="text-decoration:none;display:inline-block;margin-left:4px;">$(dynamic : icon_tiktok)$</a>
      </td>
    </tr>
  </table>


  $(dynamic : banner)$


  <p style="margin:0;font-size:10px;line-height:1.45;color:#6b5a7d;">
    Siedziba spółki: ul. Marszałkowska 80, 00‑517 Warszawa, Sąd Rejonowy dla m. st. Warszawy XII Wydział Gospodarczy KRS 0000277350, NIP: 701‑00‑65‑944, kapitał
    zakładowy: 200&nbsp;000,00&nbsp;PLN, wpłacony w całości.
  </p>
  <p style="margin:8px 0 0 0;font-size:10px;line-height:1.45;color:#6b5a7d;">
    Ta wiadomość pocztowa i wszelkie załączone do niej pliki są poufne i podlegają ochronie prawnej. Jeśli nie jesteś jej zamierzonym adresatem, prosimy o
    poinformowanie nadawcy i usunięcie wiadomości wraz z załącznikami.
  </p>
$(dynamic : tracking_logo)$
HTML;
	}
}
