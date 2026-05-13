<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Drops unused `placement` and `params` from PDF dynamic elements.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;

class m260514_000001_drop_pdf_dynamic_elements_placement_params extends Migration
{
	private const TABLE = 'a_yf_pdf_dynamic_elements';

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}
		if (isset($schema->columns['params'])) {
			$this->dropColumn(self::TABLE, 'params');
		}
		if (isset($schema->columns['placement'])) {
			$this->dropColumn(self::TABLE, 'placement');
		}
	}

	public function safeDown(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}
		if (!isset($schema->columns['placement'])) {
			$this->addColumn(self::TABLE, 'placement', $this->string(30)->notNull()->defaultValue('PLL_BODY'));
		}
		if (!isset($schema->columns['params'])) {
			$this->addColumn(self::TABLE, 'params', $this->text());
		}
	}
}
