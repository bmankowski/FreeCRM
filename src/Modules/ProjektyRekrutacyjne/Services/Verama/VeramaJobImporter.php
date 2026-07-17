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

namespace App\Modules\ProjektyRekrutacyjne\Services\Verama;

use App\Log\Log;
use App\Modules\Base\Models\Record;
use App\Modules\Users\Models\Record as UsersRecord;

final class VeramaJobImporter
{
	private const MODULE = 'ProjektyRekrutacyjne';

	/**
	 * @return array{created: int, updated: int, closed: int, processed: int}
	 */
	public function importFromPending(): array
	{
		VeramaJobFilePaths::ensureDirectories();
		$stats = ['created' => 0, 'updated' => 0, 'closed' => 0, 'processed' => 0];
		$files = glob(VeramaJobFilePaths::pending() . 'verama_*.json') ?: [];
		sort($files);

		Log::info(sprintf('Verama job import: %d file(s) in pending', count($files)), 'VERAMA');

		foreach ($files as $path) {
			try {
				$dto = VeramaJobJsonParser::parseFile($path);
				$result = $this->importOne($dto);
				VeramaJobFileOperations::moveToProcessed($path);
				$stats['processed']++;
				$stats[$result]++;
				if ($dto->status === 'CLOSED') {
					$stats['closed']++;
				}
			} catch (\Throwable $e) {
				Log::error($e, 'VERAMA');
				try {
					VeramaJobFileOperations::moveToFailed($path);
				} catch (\Throwable $moveError) {
					Log::error($moveError, 'VERAMA');
				}
				throw $e;
			}
		}

		Log::info(
			sprintf(
				'Verama job import done: created=%d updated=%d closed=%d processed=%d',
				$stats['created'],
				$stats['updated'],
				$stats['closed'],
				$stats['processed']
			),
			'VERAMA'
		);

		return $stats;
	}

	/**
	 * @return 'created'|'updated'
	 */
	private function importOne(VeramaJobImportDto $dto): string
	{
		$fields = VeramaJobFieldMapper::mapUpdatableFields($dto);
		$existingId = $this->findExistingId($dto->externalId);

		if ($existingId !== null) {
			$record = Record::getInstanceById($existingId, self::MODULE);
			foreach ($fields as $name => $value) {
				$record->set($name, $value);
			}
			$record->save();

			return 'updated';
		}

		$automatId = UsersRecord::getUserIdByName('automat');
		$record = Record::getCleanInstance(self::MODULE);
		$record->set('assigned_user_id', $automatId);
		foreach ($fields as $name => $value) {
			$record->set($name, $value);
		}
		$record->save();

		return 'created';
	}

	private function findExistingId(string $externalId): ?int
	{
		$id = (new \App\Db\Query())
			->select(['p.projektyrekrutacyjneid'])
			->from(['p' => 'u_yf_projektyrekrutacyjne'])
			->innerJoin(['e' => 'vtiger_crmentity'], 'e.crmid = p.projektyrekrutacyjneid')
			->where([
				'e.deleted' => 0,
				'e.setype' => self::MODULE,
				'p.job_source' => VeramaJobFieldMapper::JOB_SOURCE,
				'p.external_job_id' => $externalId,
			])
			->scalar();

		return $id !== false && $id !== null ? (int) $id : null;
	}
}
