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
 * Mail compose prompts after manual recruitment status transitions (kanban).
 */
class RecruitmentStatusTransitionMail
{
	private const TABLE = 'u_yf_recruitment_status_transition_mail';
	private const TEMPLATE_MODULE = 'ProjektyRekrutacyjne';

	public static function getStatusOptions(): array
	{
		return RecruitmentStatusTransition::getStatusOptions();
	}

	/**
	 * @return array<string, array<string, list<int>>>
	 */
	public static function getMatrixForDisplay(): array
	{
		$rows = (new \App\Db\Query())
			->select(['from_status', 'to_status', 'email_template_id'])
			->from(self::TABLE)
			->orderBy(['id' => SORT_ASC])
			->all();

		$matrix = [];
		foreach ($rows as $row) {
			$from = (string) $row['from_status'];
			$to = (string) $row['to_status'];
			$templateId = (int) $row['email_template_id'];
			$matrix[$from][$to][] = $templateId;
		}

		return $matrix;
	}

	/**
	 * @return array{templateIds: list<int>}|null
	 */
	public static function getPrompt(string $from, string $to): ?array
	{
		if ($from === '' || $to === '' || $from === $to) {
			return null;
		}

		$rows = (new \App\Db\Query())
			->select(['email_template_id'])
			->from(self::TABLE)
			->where(['from_status' => $from, 'to_status' => $to])
			->orderBy(['id' => SORT_ASC])
			->column();

		if ($rows === []) {
			return null;
		}

		$validIds = self::filterValidTemplateIds(array_map('intval', $rows));
		if ($validIds === []) {
			return null;
		}

		return ['templateIds' => $validIds];
	}

	/**
	 * @param list<array{from: string, to: string, templateIds: list<int>}> $entries
	 */
	public static function saveMatrix(array $entries): void
	{
		$validStatuses = array_keys(self::getStatusOptions());
		$validTemplateIds = array_flip(self::getValidTemplateIdList());

		$db = \App\Db\Db::getInstance();
		$transaction = $db->beginTransaction();

		try {
			$db->createCommand()->delete(self::TABLE)->execute();

			$seen = [];
			foreach ($entries as $entry) {
				$from = (string) ($entry['from'] ?? '');
				$to = (string) ($entry['to'] ?? '');
				$templateIds = $entry['templateIds'] ?? [];

				if ($from === '' || $to === '' || $from === $to || !\is_array($templateIds)) {
					continue;
				}

				if (!\in_array($from, $validStatuses, true) || !\in_array($to, $validStatuses, true)) {
					continue;
				}

				$usedTemplates = [];
				foreach ($templateIds as $templateId) {
					$templateId = (int) $templateId;
					if ($templateId <= 0 || !isset($validTemplateIds[$templateId])) {
						continue;
					}
					if (isset($usedTemplates[$templateId])) {
						continue;
					}
					$usedTemplates[$templateId] = true;

					$key = $from . '|' . $to . '|' . $templateId;
					if (isset($seen[$key])) {
						continue;
					}
					$seen[$key] = true;

					$db->createCommand()->insert(self::TABLE, [
						'from_status' => $from,
						'to_status' => $to,
						'email_template_id' => $templateId,
					])->execute();
				}
			}

			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * @param list<int> $templateIds
	 * @return list<int>
	 */
	public static function filterValidTemplateIds(array $templateIds): array
	{
		$valid = self::getValidTemplateIdList();
		$validSet = array_flip($valid);
		$result = [];
		foreach ($templateIds as $id) {
			$id = (int) $id;
			if ($id > 0 && isset($validSet[$id]) && !\in_array($id, $result, true)) {
				$result[] = $id;
			}
		}

		return $result;
	}

	/**
	 * @return list<int>
	 */
	private static function getValidTemplateIdList(): array
	{
		$list = \App\Email\Mail::getTempleteList(self::TEMPLATE_MODULE);
		$ids = [];
		foreach ($list as $row) {
			if (!empty($row['id'])) {
				$ids[] = (int) $row['id'];
			}
		}

		return $ids;
	}
}
