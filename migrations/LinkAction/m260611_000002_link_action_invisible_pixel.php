<?php
/**
 * FreeCRM - LinkAction open tracking: 1×1 invisible pixel markup.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260611_000002_link_action_invisible_pixel extends Migration
{
	private const PIXEL_CONTENT = '<img src="$(custom : LinkActionImageUrl|open|email|email_private)$" width="1" height="1" alt="" aria-hidden="true" style="border:0;height:1px;width:1px;max-height:1px;max-width:1px;margin:0;padding:0;opacity:0;overflow:hidden;display:block;">';

	private const ELEMENT_CODES = [
		'candidates_open_tracking_logo',
		'tracking_logo',
	];

	public function safeUp(): void
	{
		if ($this->db->getSchema()->getTableSchema('u_yf_templateelements', true) === null) {
			return;
		}

		foreach (self::ELEMENT_CODES as $code) {
			$this->update(
				'u_yf_templateelements',
				[
					'content' => self::PIXEL_CONTENT,
					'description' => $code === 'tracking_logo'
						? 'Open-tracking pixel (1×1 transparent).'
						: null,
				],
				['code' => $code]
			);
		}

		$this->update(
			'u_yf_templateelements',
			['description' => 'Niewidoczny piksel śledzenia otwarć e-mail (1×1) dla modułu Kandydaci (PL).'],
			['code' => 'candidates_open_tracking_logo', 'language' => 'pl_pl']
		);
		$this->update(
			'u_yf_templateelements',
			['description' => 'Invisible open-tracking pixel (1×1) for Candidates module emails (EN).'],
			['code' => 'candidates_open_tracking_logo', 'language' => 'en_us']
		);
	}

	public function safeDown(): void
	{
		$content = '<img src="$(custom : LinkActionImageUrl|open|email|email_private)$" width="2" height="2" alt="" style="display:block;border:0;">';

		foreach (self::ELEMENT_CODES as $code) {
			$this->update(
				'u_yf_templateelements',
				['content' => $content],
				['code' => $code]
			);
		}
	}
}
