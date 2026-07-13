<?php
/**
 * FreeCRM - Store user avatar metadata on vtiger_users instead of s_yf_record_files.
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260710_000005_users_photo_storage_columns extends Migration
{
	public function safeUp(): void
	{
		if ($this->db->getTableSchema('vtiger_users', true) === null) {
			return;
		}

		$this->addPhotoColumns();
		$this->migrateFromRecordFiles();
		$this->backfillOrphanDiskPhotos();
		$this->removeUserRowsFromRecordFiles();
	}

	public function safeDown(): void
	{
		if ($this->db->getTableSchema('vtiger_users', true) === null) {
			return;
		}
		if ($this->db->getTableSchema('vtiger_users', true)->getColumn('photo_storage_path') !== null) {
			$this->dropColumn('vtiger_users', 'photo_size_bytes');
			$this->dropColumn('vtiger_users', 'photo_mime_type');
			$this->dropColumn('vtiger_users', 'photo_storage_path');
		}
	}

	private function addPhotoColumns(): void
	{
		$schema = $this->db->getTableSchema('vtiger_users', true);
		if ($schema === null) {
			return;
		}
		if ($schema->getColumn('photo_storage_path') === null) {
			$this->addColumn('vtiger_users', 'photo_storage_path', $this->string(500)->null());
		}
		$schema = $this->db->getTableSchema('vtiger_users', true);
		if ($schema !== null && $schema->getColumn('photo_mime_type') === null) {
			$this->addColumn('vtiger_users', 'photo_mime_type', $this->string(127)->null());
		}
		$schema = $this->db->getTableSchema('vtiger_users', true);
		if ($schema !== null && $schema->getColumn('photo_size_bytes') === null) {
			$this->addColumn('vtiger_users', 'photo_size_bytes', $this->integer()->unsigned()->notNull()->defaultValue(0));
		}
	}

	private function migrateFromRecordFiles(): void
	{
		if ($this->db->getTableSchema('s_yf_record_files', true) === null) {
			return;
		}

		$rows = (new Query())
			->select([
				'u.id',
				'rf.storage_path',
				'rf.original_name',
				'rf.mime_type',
				'rf.size_bytes',
				'u.imagename',
			])
			->from(['rf' => 's_yf_record_files'])
			->innerJoin(['u' => 'vtiger_users'], 'u.id = rf.crm_record_id')
			->where(['rf.role' => 'image'])
			->orderBy(['rf.id' => SORT_DESC])
			->all($this->db);

		$migrated = [];
		foreach ($rows as $row) {
			$userId = (int) $row['id'];
			if (isset($migrated[$userId])) {
				continue;
			}
			$storagePath = (string) ($row['storage_path'] ?? '');
			if ($storagePath === '') {
				continue;
			}
			$displayName = (string) ($row['imagename'] ?? '');
			if ($displayName === '') {
				$displayName = (string) ($row['original_name'] ?? basename($storagePath));
			}
			$this->db->createCommand()->update('vtiger_users', [
				'photo_storage_path' => $storagePath,
				'photo_mime_type' => (string) ($row['mime_type'] ?? 'image/png'),
				'photo_size_bytes' => (int) ($row['size_bytes'] ?? 0),
				'imagename' => $displayName,
			], ['id' => $userId])->execute();
			$migrated[$userId] = true;
			echo sprintf("Migrated user %d photo from s_yf_record_files.\n", $userId);
		}
	}

	private function backfillOrphanDiskPhotos(): void
	{
		if (!\defined('ROOT_DIRECTORY')) {
			return;
		}

		$userIds = (new Query())
			->select(['id'])
			->from('vtiger_users')
			->where(['or', ['photo_storage_path' => null], ['photo_storage_path' => '']])
			->column($this->db);

		$storageRoot = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'Users';
		if (!is_dir($storageRoot)) {
			return;
		}

		foreach ($userIds as $userId) {
			$userId = (int) $userId;
			if ($userId <= 0) {
				continue;
			}
			$prefix = $userId . '_';
			$newestPath = null;
			$newestMtime = 0;
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($storageRoot, FilesystemIterator::SKIP_DOTS)
			);
			foreach ($iterator as $fileInfo) {
				if (!$fileInfo->isFile()) {
					continue;
				}
				$name = $fileInfo->getFilename();
				if (!str_starts_with($name, $prefix)) {
					continue;
				}
				$mtime = $fileInfo->getMTime();
				if ($mtime >= $newestMtime) {
					$newestMtime = $mtime;
					$newestPath = $fileInfo->getPathname();
				}
			}
			if ($newestPath === null) {
				continue;
			}
			$relativePath = str_replace('\\', '/', substr($newestPath, strlen(ROOT_DIRECTORY . DIRECTORY_SEPARATOR)));
			$displayName = basename($newestPath);
			if (preg_match('/^\d+_(.+)$/', $displayName, $matches)) {
				$displayName = $matches[1];
			}
			$sizeBytes = (int) @filesize($newestPath);
			$this->db->createCommand()->update('vtiger_users', [
				'photo_storage_path' => $relativePath,
				'photo_mime_type' => 'image/png',
				'photo_size_bytes' => $sizeBytes,
				'imagename' => $displayName,
			], ['id' => $userId])->execute();
			echo sprintf("Linked orphan disk photo for user %d: %s\n", $userId, $relativePath);
		}
	}

	private function removeUserRowsFromRecordFiles(): void
	{
		if ($this->db->getTableSchema('s_yf_record_files', true) === null) {
			return;
		}

		$deleted = $this->db->createCommand(
			'DELETE rf FROM s_yf_record_files rf
			 INNER JOIN vtiger_users u ON u.id = rf.crm_record_id
			 WHERE rf.role = :role'
		)->bindValue(':role', 'image')->execute();

		if ($deleted > 0) {
			echo sprintf("Removed %d user avatar row(s) from s_yf_record_files.\n", $deleted);
		}
	}
}
