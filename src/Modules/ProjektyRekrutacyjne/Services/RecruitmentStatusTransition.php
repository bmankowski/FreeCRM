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

namespace App\Modules\ProjektyRekrutacyjne\Services;

/**
 * Whitelist-based recruitment status transition rules for manual status changes.
 */
class RecruitmentStatusTransition
{
	private const TRANSITIONS_TABLE = 'u_yf_recruitment_status_transitions';
	private const SETTINGS_TABLE = 'u_yf_recruitment_settings';

	private const SUGGESTED_DEFAULTS = [
		'PPL_MANUALLY_ADDED' => ['PPL_CANDIDATE_PASSED_SCREENING', 'PPL_REJECTED_AFTER_CV'],
		'PPL_AI_ADDED' => ['PPL_CANDIDATE_PASSED_SCREENING', 'PPL_REJECTED_AFTER_CV'],
		'PPL_APPLIED' => ['PPL_REJECTED_AFTER_CV', 'PPL_CANDIDATE_PASSED_SCREENING'],
		'PPL_REJECTED_AFTER_CV' => ['PPL_CANDIDATE_PASSED_SCREENING'],
		'PPL_CANDIDATE_PASSED_SCREENING' => ['PPL_STAGE_1', 'PPL_WAITING_FOR_INTERVIEW', 'PPL_REJECTED_AFTER_VERIFICATION'],
		'PPL_STAGE_1' => ['PPL_STAGE_2', 'PPL_REJECTED_AFTER_INTERVIEW'],
		'PPL_STAGE_2' => ['PPL_STAGE_3', 'PPL_REJECTED_AFTER_INTERVIEW'],
		'PPL_STAGE_3' => ['PPL_HANDED_TO_SALES', 'PPL_TO_BE_SENT_TO_CLIENT', 'PPL_REJECTED_AFTER_INTERVIEW'],
		'PPL_WAITING_FOR_INTERVIEW' => ['PPL_HANDED_TO_SALES', 'PPL_TO_BE_SENT_TO_CLIENT', 'PPL_REJECTED_AFTER_INTERVIEW'],
		'PPL_HANDED_TO_SALES' => ['PPL_TO_BE_SENT_TO_CLIENT', 'PPL_SENT_TO_CLIENT'],
		'PPL_TO_BE_SENT_TO_CLIENT' => ['PPL_SENT_TO_CLIENT'],
		'PPL_SENT_TO_CLIENT' => ['PPL_ACCEPTED', 'PPL_REJECTED_BY_CLIENT', 'PPL_OFFER_REJECTED_BY_CANDIDATE'],
	];

	public static function getStatusOptions(): array
	{
		return \App\Modules\Settings\Workflows\Models\RelationTrigger::getRecruitmentStatusOptions();
	}

	public static function isConfigured(): bool
	{
		$value = (new \App\Db\Query())
			->select(['configured'])
			->from(self::SETTINGS_TABLE)
			->where(['id' => 1])
			->scalar();

		return (int) $value === 1;
	}

	public static function isAllowed(string $from, string $to): bool
	{
		if ($from === $to) {
			return true;
		}

		if (!self::isConfigured()) {
			return true;
		}

		return (new \App\Db\Query())
			->from(self::TRANSITIONS_TABLE)
			->where(['from_status' => $from, 'to_status' => $to])
			->exists();
	}

	public static function getAdjacencyMap(): array
	{
		if (!self::isConfigured()) {
			return [];
		}

		$rows = (new \App\Db\Query())
			->select(['from_status', 'to_status'])
			->from(self::TRANSITIONS_TABLE)
			->all();

		$map = [];
		foreach ($rows as $row) {
			$from = (string) $row['from_status'];
			$to = (string) $row['to_status'];
			$map[$from][] = $to;
		}

		return $map;
	}

	/**
	 * @return array<string, list<string>>
	 */
	public static function getSavedMatrix(): array
	{
		$rows = (new \App\Db\Query())
			->select(['from_status', 'to_status'])
			->from(self::TRANSITIONS_TABLE)
			->all();

		$matrix = [];
		foreach ($rows as $row) {
			$from = (string) $row['from_status'];
			$to = (string) $row['to_status'];
			$matrix[$from][] = $to;
		}

		return $matrix;
	}

	/**
	 * @return array<string, list<string>>
	 */
	public static function getSuggestedDefaults(): array
	{
		return self::SUGGESTED_DEFAULTS;
	}

	/**
	 * @return array<string, list<string>>
	 */
	public static function getMatrixForDisplay(): array
	{
		if (self::isConfigured()) {
			return self::getSavedMatrix();
		}

		return self::getSuggestedDefaults();
	}

	/**
	 * @param list<array{from: string, to: string}> $pairs
	 */
	public static function saveMatrix(array $pairs): void
	{
		$validStatuses = array_keys(self::getStatusOptions());
		$seen = [];

		$db = \App\Db\Db::getInstance();
		$transaction = $db->beginTransaction();

		try {
			$db->createCommand()->delete(self::TRANSITIONS_TABLE)->execute();

			foreach ($pairs as $pair) {
				$from = (string) ($pair['from'] ?? '');
				$to = (string) ($pair['to'] ?? '');

				if ($from === '' || $to === '' || $from === $to) {
					continue;
				}

				if (!\in_array($from, $validStatuses, true) || !\in_array($to, $validStatuses, true)) {
					continue;
				}

				$key = $from . '|' . $to;
				if (isset($seen[$key])) {
					continue;
				}
				$seen[$key] = true;

				$db->createCommand()->insert(self::TRANSITIONS_TABLE, [
					'from_status' => $from,
					'to_status' => $to,
				])->execute();
			}

			$db->createCommand()->upsert(
				self::SETTINGS_TABLE,
				['id' => 1, 'configured' => 1],
				['configured' => 1]
			)->execute();

			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}
}
