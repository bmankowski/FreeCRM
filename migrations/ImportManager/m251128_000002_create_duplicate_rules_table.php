<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Adds a table for storing user-defined duplicate detection rules.
 */

declare(strict_types=1);

use yii\db\Migration;

class m251128_000002_create_duplicate_rules_table extends Migration
{
	public function safeUp(): void
	{
		$this->createTable('#__import_duplicate_rules', [
			'module' => $this->string(50)->notNull(),
			'rules' => $this->text(),
			'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
			'updated_at' => $this->dateTime(),
		]);

		$this->addPrimaryKey('pk_import_duplicate_rules', '#__import_duplicate_rules', ['module']);
	}

	public function safeDown(): void
	{
		$this->dropTable('#__import_duplicate_rules');
	}
}

