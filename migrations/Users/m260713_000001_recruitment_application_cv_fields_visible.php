<?php
/**
 * RecruitmentApplication: show CV document reference and original filename on detail/edit.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260713_000001_recruitment_application_cv_fields_visible extends Migration
{
	public $transaction = false;

	private const TABID = 129;

	public function safeUp(): void
	{
		$this->update(
			'vtiger_field',
			['presence' => 0, 'sequence' => 1],
			['tabid' => self::TABID, 'fieldname' => 'cv_document_id']
		);
		$this->update(
			'vtiger_field',
			['presence' => 0, 'sequence' => 2],
			['tabid' => self::TABID, 'fieldname' => 'cv_original_filename']
		);
		$this->update(
			'vtiger_field',
			['presence' => 0, 'sequence' => 3],
			['tabid' => self::TABID, 'fieldname' => 'cv_saved_filename']
		);
		$this->update(
			'vtiger_field',
			['presence' => 0, 'sequence' => 4],
			['tabid' => self::TABID, 'fieldname' => 'cv_attachment_url']
		);

		$this->clearFieldCache();
	}

	public function safeDown(): void
	{
		$this->update(
			'vtiger_field',
			['presence' => 2],
			[
				'tabid' => self::TABID,
				'fieldname' => ['cv_document_id', 'cv_original_filename'],
			]
		);

		$this->clearFieldCache();
	}

	private function clearFieldCache(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::TABID);
	}
}
