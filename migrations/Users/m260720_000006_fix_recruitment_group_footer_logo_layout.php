<?php
/**
 * FreeCRM - Fix recruitment_group_footer layout: logo under text (not beside — wide mailLogo overlaps).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260720_000006_fix_recruitment_group_footer_logo_layout extends Migration
{
	private const CODE = 'recruitment_group_footer';

	public function safeUp(): void
	{
		$this->updateFooterContent($this->footerHtmlFixed());
	}

	public function safeDown(): void
	{
		// Pre-fix beside-logo layout (wide mailLogo overlapped greeting text).
		$this->updateFooterContent($this->footerHtmlBesideLogo());
	}

	private function updateFooterContent(string $content): void
	{
		if ($this->db->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		$id = (int) (new Query())
			->select(['templateelementsid'])
			->from('u_yf_templateelements')
			->where(['code' => self::CODE])
			->scalar($this->db);
		if ($id <= 0) {
			echo 'Skip: TemplateElement ' . self::CODE . " not found.\n";

			return;
		}

		$this->update('u_yf_templateelements', ['content' => $content], ['templateelementsid' => $id]);
		echo "Updated TemplateElement " . self::CODE . " (id={$id}) logo layout.\n";
	}

	private function footerHtmlFixed(): string
	{
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

	private function footerHtmlBesideLogo(): string
	{
		return <<<'HTML'
  <p><br></p>

  <table border="0" width="100%" style="max-width:640px;background-color:#ffffff;">
    <tr>
      <td>
        <table border="0" width="100%">
          <tr>
            <td style="width:112px;padding-right:16px;vertical-align:top;">
              <span style="display:inline-block;width:96px;">$(organization : mailLogo)$</span>
            </td>
            <td style="font-family:Lato, Arial, Helvetica, sans-serif;">
              <p style="margin:0 0 4px 0;font-size:15px;line-height:1.35;font-weight:700;color:#8B7AB5;">Pozdrawiamy,</p>
              <p style="margin:0 0 6px 0;font-size:15px;line-height:1.25;font-weight:900;color:#632E8E;">Rekrutacja IT CONNECT</p>
              <p style="margin:0 0 2px 0;font-size:15px;line-height:1.35;font-weight:700;color:#F58220;">Zespół rekrutacyjny</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <tr>
      <td style="padding:0px;">
        <table border="0" width="100%">
          <tr>
            <td style="font-family:Lato, Arial, Helvetica, sans-serif;">
              <table border="0">
                <tr>
                  <td style="width:20px;padding:0 8px 8px 0;">$(dynamic : icon_mail)$
                  </td>
                  <td style="padding:0 0 8px 0;font-size:14px;line-height:1.4;color:#632E8E;font-weight:700;">
                    <a href="mailto:rekrutacja@itconnect.pl" style="color:#632E8E;text-decoration:underline;">rekrutacja@itconnect.pl</a>
                  </td>
                </tr>
                <tr>
                  <td style="width:20px;padding:0 8px 8px 0;">$(dynamic : icon_web)$
                  </td>
                  <td style="padding:0 0 8px 0;font-size:14px;line-height:1.4;">
                    <a href="https://www.itconnect.pl/" style="color:#632E8E;text-decoration:underline;">www.itconnect.pl</a>
                  </td>
                </tr>
                <tr>
                  <td style="width:20px;padding:0 8px 0 0;">
                    $(dynamic : icon_address)$
                  </td>
                  <td style="padding:0;font-size:14px;line-height:1.45;color:#632E8E;font-weight:700;">
                    ul.&nbsp;Chłodna&nbsp;51, piętro&nbsp;22<br>00‑867&nbsp;Warszawa
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
  <table>
    <tr>
      <td style="vertical-align:middle;">
        <p style="margin:5px 0 0 0;font-size:13px;line-height:1.4;font-family:Lato, Arial, Helvetica, sans-serif;">
          <a href="https://www.itconnect.pl/polityka-prywatnosci/" style="color:#632E8E;text-decoration:underline;">Polityka prywatności</a>
        </p>
      </td>
      <td style="vertical-align:middle;padding:0 0 0 12px;white-space:nowrap;font-family:Lato, Arial, Helvetica, sans-serif;">
        <a href="https://www.facebook.com/ITCPeopleinIT/" style="text-decoration:none;display:inline-block;margin-left:4px;">
          $(dynamic : icon_facebook)$
        </a>
        <a href="https://www.instagram.com/itcpeopleinit/" style="text-decoration:none;display:inline-block;margin-left:4px;">
          $(dynamic : icon_instagram)$
        </a>
        <a href="https://pl.linkedin.com/company/it-connect-people-in-it" style="text-decoration:none;display:inline-block;margin-left:4px;">
          $(dynamic : icon_linkedin)$
        </a>
        <a href="https://www.youtube.com/@ITCONNECT-PeopleinIT" style="text-decoration:none;display:inline-block;margin-left:4px;">
          $(dynamic : icon_youtube)$
        </a>
        <a href="https://www.tiktok.com/@itconnect2007" style="text-decoration:none;display:inline-block;margin-left:4px;">
          $(dynamic : icon_tiktok)$
        </a>
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
