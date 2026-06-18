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

namespace App\Modules\ProjektyRekrutacyjne\Services;

/**
 * Mail compose prompts after manual recruitment status transitions (kanban).
 */
class RecruitmentStatusTransitionMail
{
	private const TABLE = 'u_yf_recruitment_status_transition_mail';

	public static function getStatusOptions(): array
	{
		return RecruitmentStatusTransition::getStatusOptions();
	}

	/**
	 * @return array<string, array<string, list<string>>>
	 */
	public static function getMatrixForDisplay(): array
	{
		$rows = (new \App\Db\Query())
			->select(['from_status', 'to_status', 'short_name'])
			->from(self::TABLE)
			->orderBy(['id' => SORT_ASC])
			->all();

		$matrix = [];
		foreach ($rows as $row) {
			$from = (string) $row['from_status'];
			$to = (string) $row['to_status'];
			$shortName = trim((string) $row['short_name']);
			if ($shortName === '') {
				continue;
			}
			$matrix[$from][$to][] = $shortName;
		}

		return $matrix;
	}

	/**
	 * @return array{templateIds: list<int>}|null
	 */
	public static function getPrompt(string $from, string $to, int $accountId): ?array
	{
		if ($from === '' || $to === '' || $from === $to) {
			return null;
		}

		$shortNames = (new \App\Db\Query())
			->select(['short_name'])
			->from(self::TABLE)
			->where(['from_status' => $from, 'to_status' => $to])
			->orderBy(['id' => SORT_ASC])
			->column();

		if ($shortNames === []) {
			return null;
		}

		$normalized = [];
		foreach ($shortNames as $shortName) {
			$shortName = trim((string) $shortName);
			if ($shortName !== '' && !\in_array($shortName, $normalized, true)) {
				$normalized[] = $shortName;
			}
		}
		if ($normalized === []) {
			return null;
		}

		$templateIds = \App\Modules\EmailTemplates\Models\RecruitmentTemplate::resolveShortNamesForAccount(
			$normalized,
			$accountId
		);
		if ($templateIds === []) {
			return null;
		}

		return ['templateIds' => $templateIds];
	}

	/**
	 * @param list<array{from: string, to: string, shortNames: list<string>}> $entries
	 */
	public static function saveMatrix(array $entries): void
	{
		$validStatuses = array_keys(self::getStatusOptions());
		$validShortNames = array_flip(\App\Modules\EmailTemplates\Models\RecruitmentTemplate::getDistinctShortNames());

		$db = \App\Db\Db::getInstance();
		$transaction = $db->beginTransaction();

		try {
			$db->createCommand()->delete(self::TABLE)->execute();

			$seen = [];
			foreach ($entries as $entry) {
				$from = (string) ($entry['from'] ?? '');
				$to = (string) ($entry['to'] ?? '');
				$shortNames = $entry['shortNames'] ?? [];

				if ($from === '' || $to === '' || $from === $to || !\is_array($shortNames)) {
					continue;
				}

				if (!\in_array($from, $validStatuses, true) || !\in_array($to, $validStatuses, true)) {
					continue;
				}

				$usedShortNames = [];
				foreach ($shortNames as $shortName) {
					$shortName = trim((string) $shortName);
					if ($shortName === '' || !isset($validShortNames[$shortName])) {
						continue;
					}
					if (isset($usedShortNames[$shortName])) {
						continue;
					}
					$usedShortNames[$shortName] = true;

					$key = $from . '|' . $to . '|' . $shortName;
					if (isset($seen[$key])) {
						continue;
					}
					$seen[$key] = true;

					$db->createCommand()->insert(self::TABLE, [
						'from_status' => $from,
						'to_status' => $to,
						'short_name' => $shortName,
					])->execute();
				}
			}

			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}
}
