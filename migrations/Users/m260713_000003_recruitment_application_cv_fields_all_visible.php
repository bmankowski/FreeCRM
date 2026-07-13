<?php
/**
 * RecruitmentApplication: keep all CV block fields visible on detail/edit.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260713_000003_recruitment_application_cv_fields_all_visible extends Migration
{
	public $transaction = false;

	private const TABID = 129;

	/** @var list<string> */
	private const CV_FIELDS = [
		'cv_document_id',
		'cv_original_filename',
		'cv_saved_filename',
		'cv_attachment_url',
	];

	public function safeUp(): void
	{
		foreach (self::CV_FIELDS as $sequence => $fieldName) {
			$this->update(
				'vtiger_field',
				['presence' => 0, 'sequence' => $sequence + 1],
				['tabid' => self::TABID, 'fieldname' => $fieldName]
			);
		}

		$this->clearFieldCache();
	}

	public function safeDown(): void
	{
		$this->clearFieldCache();
	}

	private function clearFieldCache(): void
	{
		\App\Cache\Cache::delete('ModuleFields', (string) self::TABID);
		\App\Cache\Cache::delete('fieldInfo', (string) self::TABID);
	}
}
