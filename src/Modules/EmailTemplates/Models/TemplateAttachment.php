<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\EmailTemplates\Models;

class TemplateAttachment
{
	private const TEMPLATE_MODULE = 'EmailTemplates';
	private const DOCUMENT_MODULE = 'Documents';

	/**
	 * @return list<array{id: int, name: string, size: int, filetype: string, hasFile: bool, downloadUrl: string}>
	 */
	public static function listForTemplate(int $templateId): array
	{
		if ($templateId <= 0) {
			return [];
		}
		$ref = self::referenceInfo();
		$table = $ref['table'];
		$documentColumn = $ref['base'];
		$templateColumn = $ref['rel'];

		$rows = (new \App\Db\Query())
			->select([
				"{$table}.{$documentColumn} AS document_id",
				'vtiger_notes.title AS notes_title',
				'vtiger_notes.size_bytes',
				'vtiger_notes.mime_type',
				'vtiger_notes.location_type',
				'vtiger_notes.active',
				'vtiger_notes.original_name',
				'vtiger_notes.storage_path',
			])
			->from($table)
			->innerJoin('vtiger_crmentity', "vtiger_crmentity.crmid = {$table}.{$documentColumn}")
			->innerJoin('vtiger_notes', "vtiger_notes.notesid = {$table}.{$documentColumn}")
			->where([
				'vtiger_crmentity.deleted' => 0,
				"{$table}.{$templateColumn}" => $templateId,
			])
			->orderBy(["{$table}.{$documentColumn}" => SORT_ASC])
			->all();

		$out = [];
		foreach ($rows as $row) {
			$documentId = (int) ($row['document_id'] ?? 0);
			if ($documentId <= 0) {
				continue;
			}
			$out[] = self::formatRow($documentId, $row);
		}

		return $out;
	}

	/**
	 * @return list<int>
	 */
	public static function getDocumentIdsForTemplate(int $templateId): array
	{
		if ($templateId <= 0) {
			return [];
		}
		$ref = self::referenceInfo();

		$ids = (new \App\Db\Query())
			->select(["{$ref['table']}.{$ref['base']}"])
			->from($ref['table'])
			->innerJoin('vtiger_crmentity', "vtiger_crmentity.crmid = {$ref['table']}.{$ref['base']}")
			->where([
				'vtiger_crmentity.deleted' => 0,
				"{$ref['table']}.{$ref['rel']}" => $templateId,
			])
			->column();

		return array_values(array_map('intval', $ids));
	}

	/**
	 * @param list<int> $documentIds
	 */
	public static function link(int $templateId, array $documentIds): void
	{
		if ($templateId <= 0 || $documentIds === []) {
			return;
		}
		self::assertTemplateEditable($templateId);

		$existing = array_flip(self::getDocumentIdsForTemplate($templateId));
		$newIds = [];
		$additionalBytes = 0;
		$additionalCount = 0;

		foreach ($documentIds as $documentId) {
			$documentId = (int) $documentId;
			if ($documentId <= 0 || isset($existing[$documentId])) {
				continue;
			}
			$meta = self::loadValidDocumentMeta($documentId);
			$newIds[] = $documentId;
			$additionalBytes += (int) ($meta['size'] ?? 0);
			$additionalCount++;
		}

		if ($newIds === []) {
			return;
		}

		self::assertWithinLimits($templateId, $additionalBytes, $additionalCount);

		$parentModule = \App\Modules\Base\Models\Module::getInstance(self::TEMPLATE_MODULE);
		$relatedModule = \App\Modules\Base\Models\Module::getInstance(self::DOCUMENT_MODULE);
		$relationModel = \App\Modules\Base\Models\Relation::getInstance($parentModule, $relatedModule);
		if (!$relationModel) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}

		foreach ($newIds as $documentId) {
			$relationModel->addRelation($templateId, $documentId);
		}

		self::clearCaches($templateId, $newIds);
	}

	public static function unlink(int $templateId, int $documentId): void
	{
		if ($templateId <= 0 || $documentId <= 0) {
			return;
		}
		self::assertTemplateEditable($templateId);

		$ref = self::referenceInfo();
		$deleted = \App\Db\Db::getInstance()->createCommand()->delete($ref['table'], [
			$ref['base'] => $documentId,
			$ref['rel'] => $templateId,
		])->execute();
		if ($deleted === 0) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}

		self::clearCaches($templateId, [$documentId]);
	}

	public static function assertAllFilesPresent(int $templateId): void
	{
		if ($templateId <= 0) {
			return;
		}
		foreach (self::listForTemplate($templateId) as $item) {
			if (empty($item['hasFile'])) {
				throw new \App\Exceptions\AppException('LBL_ATTACHMENT_FILE_MISSING');
			}
		}
	}

	public static function assertWithinLimits(int $templateId, int $additionalBytes, int $additionalCount): void
	{
		if ($additionalCount <= 0 && $additionalBytes <= 0) {
			return;
		}

		$current = self::listForTemplate($templateId);
		$currentCount = count($current);
		$currentBytes = 0;
		foreach ($current as $item) {
			$currentBytes += (int) ($item['size'] ?? 0);
		}

		$maxFiles = \App\Modules\Mail\Models\ComposeAttachment::maxFiles();
		if ($currentCount + $additionalCount > $maxFiles) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_MAX_FILES');
		}

		$maxTotal = \App\Modules\Mail\Models\ComposeAttachment::maxTotalBytes();
		if ($currentBytes + $additionalBytes > $maxTotal) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_TOTAL_TOO_LARGE');
		}

		$maxFile = \App\Modules\Mail\Models\ComposeAttachment::maxFileBytes();
		if ($additionalBytes > $maxFile) {
			throw new \App\Exceptions\AppException('LBL_MAIL_ATTACHMENT_TOO_LARGE');
		}
	}

	/**
	 * @param list<int> $documentIds
	 */
	public static function clearCaches(int $templateId, array $documentIds = []): void
	{
		\App\Cache\Cache::delete('MailAttachmentsFromTemplete', (string) $templateId);
		$allIds = self::getDocumentIdsForTemplate($templateId);
		if ($allIds !== []) {
			sort($allIds);
			\App\Cache\Cache::delete('MailAttachmentsFromDocument', 'v2:' . implode(',', $allIds));
		}
	}

	/**
	 * @return array{table: string, base: string, rel: string}
	 */
	private static function referenceInfo(): array
	{
		return \App\Modules\Base\Models\Relation::getReferenceTableInfo(
			self::TEMPLATE_MODULE,
			self::DOCUMENT_MODULE
		);
	}

	private static function assertTemplateEditable(int $templateId): void
	{
		if (!\App\Records\Record::isExists($templateId, self::TEMPLATE_MODULE)) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		if (!\App\Security\Privilege::isPermitted(self::TEMPLATE_MODULE, 'EditView', $templateId)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	/**
	 * @return array{size: int}
	 */
	private static function loadValidDocumentMeta(int $documentId): array
	{
		if (!\App\Records\Record::isExists($documentId, self::DOCUMENT_MODULE)) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		if (!\App\Security\Privilege::isPermitted(self::DOCUMENT_MODULE, 'DetailView', $documentId)) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}

		$row = (new \App\Db\Query())
			->select([
				'vtiger_notes.size_bytes',
				'vtiger_notes.location_type',
				'vtiger_notes.active',
				'vtiger_notes.original_name',
				'vtiger_notes.storage_path',
			])
			->from('vtiger_notes')
			->innerJoin('vtiger_crmentity', 'vtiger_notes.notesid = vtiger_crmentity.crmid')
			->where(['vtiger_crmentity.deleted' => 0, 'vtiger_notes.notesid' => $documentId])
			->one();

		if (!$row) {
			throw new \App\Exceptions\AppException('LBL_RECORD_NOT_FOUND');
		}
		if ((string) ($row['location_type'] ?? '') !== 'internal' || (int) ($row['active'] ?? 0) !== 1) {
			throw new \App\Exceptions\AppException('LBL_EMAILTEMPLATE_ATTACHMENT_INVALID_DOCUMENT');
		}
		if (!self::documentHasPhysicalFile($documentId, $row)) {
			throw new \App\Exceptions\AppException('LBL_ATTACHMENT_FILE_MISSING');
		}

		$size = (int) ($row['size_bytes'] ?? 0);
		if ($size <= 0) {
			$size = self::physicalFileSize($documentId, $row);
		}

		return ['size' => $size];
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array{id: int, name: string, size: int, filetype: string, hasFile: bool, downloadUrl: string}
	 */
	private static function formatRow(int $documentId, array $row): array
	{
		$name = (string) ($row['notes_title'] ?? 'Document');
		$fileType = (string) ($row['mime_type'] ?? '');
		$hasFile = self::documentHasPhysicalFile($documentId, $row);
		$downloadUrl = '';
		if ($hasFile) {
			$recordModel = \App\Modules\Documents\Models\Record::getInstanceById($documentId, self::DOCUMENT_MODULE);
			$downloadUrl = (string) $recordModel->getDownloadFileURL();
		}
		$size = (int) ($row['size_bytes'] ?? 0);
		if ($hasFile && $size <= 0) {
			$size = self::physicalFileSize($documentId, $row);
		}

		return [
			'id' => $documentId,
			'name' => $name,
			'size' => $size,
			'filetype' => $fileType,
			'hasFile' => $hasFile,
			'downloadUrl' => $downloadUrl,
		];
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function physicalFileSize(int $documentId, array $row): int
	{
		$filePath = self::resolveDocumentPath($row);
		if ($filePath === false) {
			return 0;
		}

		return (int) filesize($filePath);
	}

	private static function documentHasPhysicalFile(int $documentId, array $row): bool
	{
		if ((string) ($row['location_type'] ?? '') !== 'internal' || (int) ($row['active'] ?? 0) !== 1) {
			return false;
		}
		if ((string) ($row['storage_path'] ?? '') === '') {
			return false;
		}

		$filePath = self::resolveDocumentPath($row);

		return $filePath !== false && is_file($filePath);
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function resolveDocumentPath(array $row): string|false
	{
		return \App\Modules\Documents\Models\Record::resolveStoragePath(
			(string) ($row['storage_path'] ?? ''),
			(string) ($row['original_name'] ?? '') ?: null
		);
	}
}
