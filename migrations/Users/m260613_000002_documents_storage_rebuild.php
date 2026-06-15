<?php
/**
 * FreeCRM - Documents storage rebuild (additive phase).
 *
 * Run via: docker compose exec -T app php yii migrate --migrationPath=migrations/Users/ --interactive=0
 */

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

class m260613_000002_documents_storage_rebuild extends Migration
{
	public $transaction = false;

	public function safeUp(): void
	{
		$this->addNotesColumns();
		$this->createRecordFilesTable();
		$this->backfillScalarMetadata();
		$this->backfillStoragePaths();
		$this->migrateNonDocumentAttachments();
		$this->migrateUserPhotos();
		$this->verifyPathsOnDisk();
		$this->purgeOrphanNotes();
		$this->deleteStrandedAttachmentEntities();
	}

	public function safeDown(): void
	{
		echo "m260613_000002_documents_storage_rebuild: safeDown not supported — restore DB backup.\n";
	}

	private function addNotesColumns(): void
	{
		$schema = $this->db->getSchema()->getTableSchema('vtiger_notes', true);
		if ($schema === null) {
			return;
		}
		$columns = [
			'location_type' => "ENUM('internal','external') NOT NULL DEFAULT 'internal'",
			'storage_path' => 'VARCHAR(500) DEFAULT NULL',
			'original_name' => 'VARCHAR(255) DEFAULT NULL',
			'external_url' => 'VARCHAR(2048) DEFAULT NULL',
			'mime_type' => 'VARCHAR(127) DEFAULT NULL',
			'size_bytes' => 'INT(19) UNSIGNED NOT NULL DEFAULT 0',
			'download_count' => 'INT(19) UNSIGNED NOT NULL DEFAULT 0',
			'active' => 'TINYINT(1) NOT NULL DEFAULT 1',
		];
		foreach ($columns as $name => $definition) {
			if (!isset($schema->columns[$name])) {
				$this->db->createCommand("ALTER TABLE vtiger_notes ADD COLUMN {$name} {$definition}")->execute();
			}
		}
	}

	private function createRecordFilesTable(): void
	{
		if ($this->db->getSchema()->getTableSchema('s_yf_record_files', true) !== null) {
			return;
		}
		$this->db->createCommand(<<<'SQL'
CREATE TABLE `s_yf_record_files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `crm_record_id` int(19) NOT NULL,
  `role` varchar(32) NOT NULL DEFAULT 'attachment',
  `storage_path` varchar(500) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(127) NOT NULL DEFAULT 'application/octet-stream',
  `size_bytes` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_record` (`crm_record_id`, `role`),
  CONSTRAINT `fk_record_files_crmid` FOREIGN KEY (`crm_record_id`)
    REFERENCES `vtiger_crmentity` (`crmid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL)->execute();
	}

	private function backfillScalarMetadata(): void
	{
		if (!$this->columnExists('vtiger_notes', 'filelocationtype')) {
			return;
		}
		$this->db->createCommand(<<<'SQL'
UPDATE vtiger_notes
SET location_type  = CASE WHEN filelocationtype = 'E' THEN 'external' ELSE 'internal' END,
    mime_type      = NULLIF(filetype, ''),
    size_bytes     = COALESCE(filesize, 0),
    download_count = COALESCE(filedownloadcount, 0),
    active         = COALESCE(filestatus, 1),
    external_url   = CASE WHEN filelocationtype = 'E' THEN filename ELSE NULL END,
    original_name  = CASE WHEN filelocationtype = 'E' THEN NULL ELSE filename END
WHERE location_type = 'internal'
   OR storage_path IS NULL
SQL)->execute();
	}

	private function backfillStoragePaths(): void
	{
		if (!$this->tableExists('vtiger_seattachmentsrel') || !$this->tableExists('vtiger_attachments')) {
			return;
		}
		$this->db->createCommand(<<<'SQL'
UPDATE vtiger_notes n
JOIN vtiger_seattachmentsrel s ON s.crmid = n.notesid
JOIN vtiger_attachments a ON a.attachmentsid = s.attachmentsid
SET n.storage_path = CONCAT(a.path, a.attachmentsid, '_', a.name)
WHERE n.location_type = 'internal'
  AND (n.storage_path IS NULL OR n.storage_path = '')
SQL)->execute();
	}

	private function migrateNonDocumentAttachments(): void
	{
		if (!$this->tableExists('vtiger_seattachmentsrel') || !$this->tableExists('vtiger_attachments')) {
			return;
		}
		$this->db->createCommand(<<<'SQL'
INSERT INTO s_yf_record_files (crm_record_id, role, storage_path, original_name, mime_type, size_bytes)
SELECT s.crmid,
       CASE WHEN c.setype LIKE '%Image%' THEN 'image' ELSE 'attachment' END,
       CONCAT(a.path, a.attachmentsid, '_', a.name),
       a.name,
       COALESCE(a.type, 'application/octet-stream'),
       0
FROM vtiger_seattachmentsrel s
JOIN vtiger_attachments a ON a.attachmentsid = s.attachmentsid
JOIN vtiger_crmentity c ON c.crmid = s.crmid
WHERE c.setype <> 'Documents'
  AND NOT EXISTS (
    SELECT 1 FROM s_yf_record_files rf
    WHERE rf.crm_record_id = s.crmid
      AND rf.storage_path = CONVERT(CONCAT(a.path, a.attachmentsid, '_', a.name) USING utf8)
  )
SQL)->execute();
	}

	private function migrateUserPhotos(): void
	{
		if (!$this->tableExists('vtiger_salesmanattachmentsrel') || !$this->tableExists('vtiger_attachments')) {
			return;
		}
		$this->db->createCommand(<<<'SQL'
INSERT INTO s_yf_record_files (crm_record_id, role, storage_path, original_name, mime_type, size_bytes)
SELECT sar.smid,
       'image',
       CONCAT(a.path, a.attachmentsid, '_', a.name),
       a.name,
       COALESCE(a.type, 'application/octet-stream'),
       0
FROM vtiger_salesmanattachmentsrel sar
JOIN vtiger_attachments a ON a.attachmentsid = sar.attachmentsid
JOIN vtiger_crmentity c ON c.crmid = sar.smid
WHERE NOT EXISTS (
    SELECT 1 FROM s_yf_record_files rf
    WHERE rf.crm_record_id = sar.smid
      AND rf.role = 'image'
      AND rf.storage_path = CONVERT(CONCAT(a.path, a.attachmentsid, '_', a.name) USING utf8)
)
SQL)->execute();
	}

	private function verifyPathsOnDisk(): void
	{
		if (!\defined('ROOT_DIRECTORY')) {
			return;
		}

		$notes = (new Query())
			->select(['notesid', 'storage_path', 'original_name'])
			->from('vtiger_notes')
			->where(['location_type' => 'internal'])
			->andWhere(['not', ['storage_path' => null]])
			->andWhere(['<>', 'storage_path', ''])
			->all($this->db);

		foreach ($notes as $row) {
			$absolute = $this->resolveDocumentPath((string) $row['storage_path'], (string) ($row['original_name'] ?? ''));
			if ($absolute === false || !is_file($absolute)) {
				$this->db->createCommand()->update('vtiger_notes', [
					'active' => 0,
					'storage_path' => null,
				], ['notesid' => $row['notesid']])->execute();
			} else {
				$size = (int) @filesize($absolute);
				if ($size > 0) {
					$this->db->createCommand()->update('vtiger_notes', [
						'size_bytes' => $size,
					], ['notesid' => $row['notesid']])->execute();
				}
			}
		}

		$recordFiles = (new Query())
			->from('s_yf_record_files')
			->all($this->db);
		foreach ($recordFiles as $row) {
			$absolute = $this->resolveDocumentPath((string) ($row['storage_path'] ?? ''), (string) ($row['original_name'] ?? ''));
			if ($absolute !== false && is_file($absolute)) {
				$size = (int) @filesize($absolute);
				if ($size > 0 && (int) ($row['size_bytes'] ?? 0) <= 0) {
					$this->db->createCommand()->update('s_yf_record_files', [
						'size_bytes' => $size,
					], ['id' => $row['id']])->execute();
				}
			}
		}
	}

	private function purgeOrphanNotes(): void
	{
		$orphanIds = (new Query())
			->select('n.notesid')
			->from(['n' => 'vtiger_notes'])
			->leftJoin(['c' => 'vtiger_crmentity'], 'c.crmid = n.notesid')
			->where(['c.crmid' => null])
			->column($this->db);

		if ($orphanIds === []) {
			return;
		}

		$chunks = array_chunk($orphanIds, 500);
		foreach ($chunks as $chunk) {
			if ($this->tableExists('vtiger_notescf')) {
				$this->db->createCommand()->delete('vtiger_notescf', ['notesid' => $chunk])->execute();
			}
			$this->db->createCommand()->delete('vtiger_senotesrel', ['notesid' => $chunk])->execute();
			$this->db->createCommand()->delete('vtiger_notes', ['notesid' => $chunk])->execute();
		}
		$this->deactivateInternalDocsWithoutPath();
	}

	private function deactivateInternalDocsWithoutPath(): void
	{
		$this->db->createCommand(<<<'SQL'
UPDATE vtiger_notes
SET active = 0
WHERE location_type = 'internal'
  AND (storage_path IS NULL OR storage_path = '')
SQL)->execute();
	}

	private function deleteStrandedAttachmentEntities(): void
	{
		if (!$this->tableExists('vtiger_crmentity')) {
			return;
		}
		$this->db->createCommand(<<<'SQL'
DELETE c FROM vtiger_crmentity c
WHERE (c.setype LIKE '% Attachment' OR c.setype LIKE '% Image')
  AND NOT EXISTS (SELECT 1 FROM vtiger_seattachmentsrel s WHERE s.attachmentsid = c.crmid)
  AND NOT EXISTS (SELECT 1 FROM vtiger_salesmanattachmentsrel sar WHERE sar.attachmentsid = c.crmid)
SQL)->execute();
	}

	private function resolveDocumentPath(string $storagePath, string $originalName): string|false
	{
		if ($storagePath === '') {
			return false;
		}
		$decodedName = \App\Utils\ListViewUtils::decodeHtml($originalName);
		$candidates = [$storagePath];
		if ($decodedName !== '' && !str_ends_with($storagePath, $decodedName)) {
			$dir = dirname($storagePath);
			$base = basename($storagePath);
			if (preg_match('/^(\d+)_(.+)$/', $base, $matches)) {
				$candidates[] = ($dir === '.' ? '' : $dir . '/') . $matches[1] . '_' . $decodedName;
			}
		}
		foreach ($candidates as $candidate) {
			$absolute = realpath(ROOT_DIRECTORY . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate));
			if ($absolute !== false && is_file($absolute)) {
				return $absolute;
			}
		}

		return false;
	}

	private function columnExists(string $table, string $column): bool
	{
		$schema = $this->db->getSchema()->getTableSchema($table, true);

		return $schema !== null && isset($schema->columns[$column]);
	}

	private function tableExists(string $table): bool
	{
		return $this->db->getSchema()->getTableSchema($table, true) !== null;
	}
}
