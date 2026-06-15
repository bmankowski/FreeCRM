<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\Modules\Candidates\Actions;

use App\Db\Query;

/**
 * Mass-export CV-titled Documents for selected candidates as one ZIP.
 */
class ExportCvZip extends \App\Base\Controllers\BaseActionController
{
	private const MODULE = 'Candidates';

	public function checkPermission(\App\Http\Vtiger_Request $request): void
	{
		if (!\App\Modules\Users\Models\Privileges::isPermitted(self::MODULE, 'DetailView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
		if (!\App\Modules\Users\Models\Privileges::isPermitted('Documents', 'DetailView')) {
			throw new \App\Exceptions\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function process(\App\Http\Vtiger_Request $request): void
	{
		$candidateIds = self::resolveCandidateIds($request);
		if ($candidateIds === []) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('LBL_SELECT_RECORD', self::MODULE));
		}

		$zipEntries = [];
		foreach ($candidateIds as $candidateId) {
			if (!\App\Modules\Users\Models\Privileges::isPermitted(self::MODULE, 'DetailView', $candidateId)) {
				continue;
			}
			$candidate = \App\Modules\Base\Models\Record::getInstanceById($candidateId, self::MODULE);
			if (!$candidate || !$candidate->isViewable()) {
				continue;
			}
			$candidateLabel = self::sanitizeZipPathSegment((string) $candidate->getDisplayValue('name'));
			foreach (self::fetchCvDocumentsForCandidate($candidateId) as $row) {
				$documentId = (int) $row['notesid'];
				if (!\App\Modules\Users\Models\Privileges::isPermitted('Documents', 'DetailView', $documentId)) {
					continue;
				}
				$diskPath = self::resolveAttachmentDiskPath($row);
				if ($diskPath === null) {
					continue;
				}
				$baseName = self::resolveZipEntryBaseName($row, $candidateLabel, $candidateId, $documentId);
				$zipEntries[] = ['path' => $diskPath, 'name' => $baseName];
			}
		}

		if ($zipEntries === []) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('PPL_EXPORT_CV_NO_FILES', self::MODULE));
		}

		self::streamZipDownload($zipEntries);
	}

	public function validateRequest(\App\Http\Vtiger_Request $request): void
	{
		$request->validateWriteAccess();
	}

	/**
	 * @return int[]
	 */
	private static function resolveCandidateIds(\App\Http\Vtiger_Request $request): array
	{
		$selectedIds = $request->get('selected_ids');
		if (\is_array($selectedIds) && $selectedIds !== []) {
			return self::normalizeCandidateIds($selectedIds);
		}
		if (\is_string($selectedIds) && $selectedIds !== '') {
			$trimmed = trim($selectedIds);
			if (str_starts_with($trimmed, '[')) {
				$decoded = json_decode($trimmed, true);
				if (\is_array($decoded) && $decoded !== []) {
					return self::normalizeCandidateIds($decoded);
				}
			}
		}

		$fromMass = \App\Modules\Base\Actions\Mass::getRecordsListFromRequest($request);
		if (\is_array($fromMass) && $fromMass !== []) {
			return self::normalizeCandidateIds($fromMass);
		}

		return [];
	}

	/**
	 * @param array<int|string> $ids
	 * @return int[]
	 */
	private static function normalizeCandidateIds(array $ids): array
	{
		$normalized = [];
		foreach ($ids as $id) {
			$intId = (int) $id;
			if ($intId > 0) {
				$normalized[$intId] = $intId;
			}
		}

		return array_values($normalized);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function fetchCvDocumentsForCandidate(int $candidateId): array
	{
		$rows = (new Query())
			->select([
				'notesid' => 'n.notesid',
				'original_name' => 'n.original_name',
				'location_type' => 'n.location_type',
				'storage_path' => 'n.storage_path',
			])
			->from(['rel' => 'vtiger_senotesrel'])
			->innerJoin(['n' => 'vtiger_notes'], 'n.notesid = rel.notesid')
			->innerJoin(['ne' => 'vtiger_crmentity'], 'ne.crmid = n.notesid AND ne.deleted = 0')
			->where([
				'rel.crmid' => $candidateId,
				'n.location_type' => 'internal',
				'n.active' => 1,
			])
			->andWhere('LOWER(TRIM(n.title)) = :cvTitle', [':cvTitle' => 'cv'])
			->andWhere(['not', ['n.storage_path' => null]])
			->orderBy(['n.notesid' => SORT_ASC])
			->all();

		return \is_array($rows) ? $rows : [];
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function resolveAttachmentDiskPath(array $row): ?string
	{
		$storagePath = (string) ($row['storage_path'] ?? '');
		if ($storagePath === '') {
			return null;
		}
		$resolved = \App\Modules\Documents\Models\Record::resolveStoragePath(
			$storagePath,
			(string) ($row['original_name'] ?? '') ?: null
		);

		return $resolved !== false && is_file($resolved) && is_readable($resolved) ? $resolved : null;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private static function resolveZipEntryBaseName(
		array $row,
		string $candidateLabel,
		int $candidateId,
		int $documentId
	): string {
		$filename = (string) ($row['filename'] ?? '');
		if ($filename === '') {
			$filename = (string) ($row['name'] ?? 'cv');
		}
		$filename = self::sanitizeZipPathSegment($filename);
		$prefix = $candidateId . '_' . ($candidateLabel !== '' ? $candidateLabel . '_' : '') . $documentId . '_';

		return $prefix . $filename;
	}

	private static function sanitizeZipPathSegment(string $value): string
	{
		$value = html_entity_decode($value, ENT_QUOTES, \App\Core\AppConfig::main('default_charset'));
		$value = preg_replace('/[\\\\\\/:*?"<>|\\x00-\\x1F]+/u', '_', $value) ?? '';
		$value = trim($value, " \t\n\r\0\x0B._");
		if ($value === '') {
			return 'record';
		}

		return mb_substr($value, 0, 120);
	}

	/**
	 * @param array<int, array{path: string, name: string}> $zipEntries
	 */
	private static function streamZipDownload(array $zipEntries): void
	{
		$tmpDir = ROOT_DIRECTORY . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR;
		if (!is_dir($tmpDir)) {
			@mkdir($tmpDir, 0755, true);
		}
		$zipPath = $tmpDir . 'candidates_cv_' . uniqid('', true) . '.zip';

		$zip = new \ZipArchive();
		if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			throw new \App\Exceptions\AppException(\App\Runtime\Vtiger_Language_Handler::translate('PPL_EXPORT_CV_ZIP_FAILED', self::MODULE));
		}

		$usedNames = [];
		foreach ($zipEntries as $entry) {
			$entryName = $entry['name'];
			if (isset($usedNames[$entryName])) {
				++$usedNames[$entryName];
				$pathInfo = pathinfo($entryName);
				$base = $pathInfo['filename'] ?? $entryName;
				$ext = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
				$entryName = $base . '_' . $usedNames[$entryName] . $ext;
			} else {
				$usedNames[$entryName] = 1;
			}
			$zip->addFile($entry['path'], $entryName);
		}
		$zip->close();

		$downloadName = 'candidates_cv_' . date('Y-m-d_His') . '.zip';
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename="' . $downloadName . '"');
		header('Content-Length: ' . (string) filesize($zipPath));
		header('Expires: Mon, 31 Dec 2000 00:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: post-check=0, pre-check=0', false);

		readfile($zipPath);
		@unlink($zipPath);
	}
}
