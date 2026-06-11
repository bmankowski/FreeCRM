<?php
/**
 * FreeCRM - LinkAction open tracking: switch logo to 2×2 orange pixel in template elements.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/LinkAction/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260611_000001_link_action_tracking_pixel extends Migration
{
	private const PIXEL_CONTENT = '<img src="$(custom : LinkActionImageUrl|open|email|email_private)$" width="2" height="2" alt="" style="display:block;border:0;">';

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
						? 'Open-tracking pixel (2×2).'
						: null,
				],
				['code' => $code]
			);
		}

		$this->update(
			'u_yf_templateelements',
			['description' => 'Piksel śledzenia otwarć e-mail (2×2) dla modułu Kandydaci (PL).'],
			['code' => 'candidates_open_tracking_logo', 'language' => 'pl_pl']
		);
		$this->update(
			'u_yf_templateelements',
			['description' => 'Open-tracking pixel (2×2) for Candidates module emails (EN).'],
			['code' => 'candidates_open_tracking_logo', 'language' => 'en_us']
		);
	}

	public function safeDown(): void
	{
		$logoContent = '<img src="$(custom : LinkActionImageUrl|open|email|email_private)$" width="120" height="40" alt="IT Connect" style="display:block;">';

		foreach (self::ELEMENT_CODES as $code) {
			$this->update(
				'u_yf_templateelements',
				['content' => $logoContent],
				['code' => $code]
			);
		}

		$this->update(
			'u_yf_templateelements',
			['description' => 'Logo ze śledzeniem otwarć e-mail dla modułu Kandydaci (PL).'],
			['code' => 'candidates_open_tracking_logo', 'language' => 'pl_pl']
		);
		$this->update(
			'u_yf_templateelements',
			['description' => 'Open-tracking logo for Kandydaci module emails (EN).'],
			['code' => 'candidates_open_tracking_logo', 'language' => 'en_us']
		);
	}
}
