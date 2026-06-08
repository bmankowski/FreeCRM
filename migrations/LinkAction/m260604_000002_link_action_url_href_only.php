<?php
/**
 * FreeCRM - LinkActionUrl returns signed URL for href; update unsubscribe footer elements.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260604_000002_link_action_url_href_only extends Migration
{
	private const FOOTERS = [
		'pl_pl' => '<p style="font-size:12px;color:#666;margin-top:24px;">Jeśli nie chcesz otrzymywać od nas więcej wiadomości i chciałbyś, abyśmy usunęli ten email z naszej bazy, możesz się wypisać klikając tutaj: <a href="$(custom : LinkActionUrl|unsubscribe|future_contact|email_prywatny)$">Wypisuje się</a></p>',
		'en_us' => '<p style="font-size:12px;color:#666;margin-top:24px;">If you no longer wish to receive messages from us and would like us to remove your email from our database, you can unsubscribe here: <a href="$(custom : LinkActionUrl|unsubscribe|future_contact|email_prywatny)$">Unsubscribe</a></p>',
	];

	public function safeUp(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}
		foreach (self::FOOTERS as $language => $content) {
			$this->update(
				'u_yf_templateelements',
				['content' => $content],
				[
					'code' => 'kandydaci_unsubscribe_footer',
					'module_name' => 'Kandydaci',
					'language' => $language,
				]
			);
		}
	}

	public function safeDown(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}
		$legacy = [
			'pl_pl' => '<p style="font-size:12px;color:#666;margin-top:24px;">Jeśli nie chcesz otrzymywać od nas więcej wiadomości marketingowych, możesz się wypisać:<br>$(custom : LinkActionUrl|unsubscribe|future_contact|newsletter_email)$</p>',
			'en_us' => '<p style="font-size:12px;color:#666;margin-top:24px;">If you no longer wish to receive marketing messages from us, you can unsubscribe:<br>$(custom : LinkActionUrl|unsubscribe|future_contact|newsletter_email)$</p>',
		];
		foreach ($legacy as $language => $content) {
			$this->update(
				'u_yf_templateelements',
				['content' => $content],
				[
					'code' => 'kandydaci_unsubscribe_footer',
					'module_name' => 'Kandydaci',
					'language' => $language,
				]
			);
		}
	}
}
