<?php
/**
 * FreeCRM - EmailTemplates standard attachments: enable Add on Documents related list.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;

class m260613_000001_emailtemplates_standard_attachments extends Migration
{
	private const RELATION_ID = 525;

	public function safeUp(): void
	{
		$this->update(
			'vtiger_relatedlists',
			['actions' => 'ADD,SELECT'],
			['relation_id' => self::RELATION_ID]
		);
	}

	public function safeDown(): void
	{
		$this->update(
			'vtiger_relatedlists',
			['actions' => 'SELECT'],
			['relation_id' => self::RELATION_ID]
		);
	}
}
