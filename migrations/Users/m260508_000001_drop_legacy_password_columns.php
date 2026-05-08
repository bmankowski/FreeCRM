<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * Drop the Vtiger-era legacy password storage columns now that all hashes
 * are Argon2id (self-describing) and the confirm-password field is a
 * UI-only retype check, never persisted.
 *
 * Run via: yii migrate --migrationPath=migrations/Users/
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260508_000001_drop_legacy_password_columns extends Migration
{
	private const TABLE = 'vtiger_users';
	private const INDEX_NAME = 'user_user_password_idx';

	public function safeUp(): void
	{
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		// Drop legacy index that referenced the password column. MySQL refuses to
		// drop a column referenced by an index, so this must come first.
		if ($this->indexExists(self::TABLE, self::INDEX_NAME)) {
			$this->dropIndex(self::INDEX_NAME, self::TABLE);
		}

		if (isset($schema->columns['confirm_password'])) {
			$this->dropColumn(self::TABLE, 'confirm_password');
		}

		if (isset($schema->columns['crypt_type'])) {
			$this->dropColumn(self::TABLE, 'crypt_type');
		}

		// confirm_password was registered as a Users module field. user_password
		// stays - the column stays. crypt_type was never a vtiger_field.
		$this->db->createCommand()
			->delete('vtiger_field', [
				'tablename' => self::TABLE,
				'columnname' => 'confirm_password',
			])
			->execute();
	}

	public function safeDown(): void
	{
		// Down direction recreates the columns *empty*. It does NOT restore the
		// legacy MD5-crypt hashes or the confirm_password mirror; that data is
		// gone after up(). This exists only so that yii migrate/down does not
		// fail mid-run; rolling back is not a real recovery path for auth.
		$schema = $this->db->getSchema()->getTableSchema(self::TABLE, true);
		if ($schema === null) {
			return;
		}

		if (!isset($schema->columns['crypt_type'])) {
			$this->addColumn(self::TABLE, 'crypt_type', $this->string(20)->notNull()->defaultValue('MD5'));
		}
		if (!isset($schema->columns['confirm_password'])) {
			$this->addColumn(self::TABLE, 'confirm_password', $this->string(300));
		}

		if (!$this->indexExists(self::TABLE, self::INDEX_NAME)) {
			$this->createIndex(self::INDEX_NAME, self::TABLE, 'user_password');
		}
	}

	private function indexExists(string $table, string $index): bool
	{
		$schema = $this->db->getSchema();
		$tableSchema = $schema->getTableSchema($table, true);
		if ($tableSchema === null) {
			return false;
		}
		$indexes = $schema->findUniqueIndexes($tableSchema);
		if (isset($indexes[$index])) {
			return true;
		}
		try {
			$rows = $this->db->createCommand(
				'SHOW INDEX FROM ' . $this->db->quoteTableName($table) . ' WHERE Key_name = :name',
				[':name' => $index]
			)->queryAll();
			return !empty($rows);
		} catch (\Throwable $e) {
			return false;
		}
	}
}
