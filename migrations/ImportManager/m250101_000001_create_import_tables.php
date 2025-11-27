<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Database objects for the ImportManager module.
 */

declare(strict_types=1);

use yii\db\Migration;

class m250101_000001_create_import_tables extends Migration
{
	public function safeUp(): void
	{
		$this->createTable('#__import_batches', [
			'id' => $this->primaryKey(),
			'module' => $this->string(50)->notNull(),
			'created_by' => $this->integer()->notNull(),
			'status' => $this->string(20)->notNull()->defaultValue('pending'),
			'duplicate_strategy' => $this->string(32)->notNull()->defaultValue('skip'),
			'queue_reference' => $this->integer(),
			'file_name' => $this->string(255)->notNull(),
			'file_path' => $this->string(255)->notNull(),
			'storage_path' => $this->string(255)->notNull(),
			'file_size' => $this->bigInteger()->notNull(),
			'file_hash' => $this->string(64)->notNull(),
			'format' => $this->string(10)->notNull(),
			'delimiter' => $this->string(5),
			'enclosure' => $this->string(5),
			'encoding' => $this->string(32)->notNull()->defaultValue('UTF-8'),
			'xpath' => $this->string(255),
			'options' => $this->text(),
			'total_rows' => $this->integer()->unsigned()->defaultValue(0),
			'processed_rows' => $this->integer()->unsigned()->defaultValue(0),
			'error_rows' => $this->integer()->unsigned()->defaultValue(0),
			'preview_rows' => $this->integer()->unsigned()->defaultValue(0),
			'queued_at' => $this->dateTime(),
			'started_at' => $this->dateTime(),
			'finished_at' => $this->dateTime(),
			'cleanup_after' => $this->dateTime(),
			'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
			'updated_at' => $this->dateTime(),
			'notes' => $this->text(),
		]);

		$this->createIndex('idx_import_batches_status', '#__import_batches', ['status']);
		$this->createIndex('idx_import_batches_module', '#__import_batches', ['module']);
		$this->createIndex('idx_import_batches_cleanup', '#__import_batches', ['cleanup_after']);

		$this->createTable('#__import_mappings', [
			'id' => $this->primaryKey(),
			'batch_id' => $this->integer()->notNull(),
			'module' => $this->string(50)->notNull(),
			'mapping' => $this->text()->notNull(),
			'default_values' => $this->text(),
			'duplicate_sets' => $this->text(),
			'options' => $this->text(),
			'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
			'updated_at' => $this->dateTime(),
		]);

		$this->createIndex('idx_import_mappings_batch', '#__import_mappings', ['batch_id']);

		$this->createTable('#__import_logs', [
			'id' => $this->primaryKey(),
			'batch_id' => $this->integer()->notNull(),
			'row_number' => $this->integer()->unsigned(),
			'record_id' => $this->integer(),
			'status' => $this->string(20)->notNull()->defaultValue('info'),
			'stage' => $this->string(32)->notNull(),
			'message' => $this->text(),
			'payload' => $this->text(),
			'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
		]);

		$this->createIndex('idx_import_logs_batch', '#__import_logs', ['batch_id']);
		$this->createIndex('idx_import_logs_status', '#__import_logs', ['status']);

		$this->addForeignKey(
			'fk_import_mappings_batch',
			'#__import_mappings',
			'batch_id',
			'#__import_batches',
			'id',
			'CASCADE',
			'CASCADE'
		);

		$this->addForeignKey(
			'fk_import_logs_batch',
			'#__import_logs',
			'batch_id',
			'#__import_batches',
			'id',
			'CASCADE',
			'CASCADE'
		);
	}

	public function safeDown(): void
	{
		$this->dropForeignKey('fk_import_logs_batch', '#__import_logs');
		$this->dropForeignKey('fk_import_mappings_batch', '#__import_mappings');

		$this->dropTable('#__import_logs');
		$this->dropTable('#__import_mappings');
		$this->dropTable('#__import_batches');
	}
}

