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
 * Mail actions after manual recruitment status transitions (kanban).
 */
class RecruitmentStatusTransitionMail
{
	public const DELIVERY_PROMPT = 'prompt';

	public const DELIVERY_AUTO = 'auto';

	private const TABLE = 'u_yf_recruitment_status_transition_mail';

	public static function getStatusOptions(): array
	{
		return RecruitmentStatusTransition::getStatusOptions();
	}

	/**
	 * @return array<string, array<string, list<array{shortName: string, deliveryMode: string}>>>
	 */
	public static function getMatrixForDisplay(): array
	{
		$rows = (new \App\Db\Query())
			->select(['from_status', 'to_status', 'short_name', 'delivery_mode'])
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
			$matrix[$from][$to][] = [
				'shortName' => $shortName,
				'deliveryMode' => self::normalizeDeliveryMode((string) ($row['delivery_mode'] ?? '')),
			];
		}

		return $matrix;
	}

	/**
	 * @return list<array{templateId: int, deliveryMode: string, shortName: string}>
	 */
	public static function resolveMailActions(string $from, string $to, int $accountId): array
	{
		if ($from === '' || $to === '' || $from === $to || $accountId <= 0) {
			return [];
		}

		$rows = (new \App\Db\Query())
			->select(['short_name', 'delivery_mode'])
			->from(self::TABLE)
			->where(['from_status' => $from, 'to_status' => $to])
			->orderBy(['id' => SORT_ASC])
			->all();

		if ($rows === []) {
			return [];
		}

		$actions = [];
		foreach ($rows as $row) {
			$shortName = trim((string) $row['short_name']);
			if ($shortName === '') {
				continue;
			}
			$templateIds = \App\Modules\EmailTemplates\Models\RecruitmentTemplate::resolveShortNamesForAccount(
				[$shortName],
				$accountId
			);
			if ($templateIds === []) {
				continue;
			}
			$actions[] = [
				'templateId' => (int) $templateIds[0],
				'deliveryMode' => self::normalizeDeliveryMode((string) ($row['delivery_mode'] ?? '')),
				'shortName' => $shortName,
			];
		}

		return $actions;
	}

	/**
	 * @param list<array{templateId: int, shortName: string}> $items
	 * @return array{sent: int, failed: int, failedShortNames: list<string>}
	 */
	public static function sendAutoTemplates(
		int $candidateId,
		int $projectId,
		array $items,
		int $userId
	): array {
		$result = [
			'sent' => 0,
			'failed' => 0,
			'failedShortNames' => [],
		];
		if ($items === [] || $candidateId <= 0 || $projectId <= 0 || $userId <= 0) {
			return $result;
		}

		$recipient = \App\Modules\Candidates\Models\RelatedListLeftSideEmail::resolvePrimaryEmailField($candidateId);
		if ($recipient === null) {
			foreach ($items as $item) {
				++$result['failed'];
				$result['failedShortNames'][] = (string) ($item['shortName'] ?? '');
			}

			return $result;
		}

		foreach ($items as $item) {
			$templateId = (int) ($item['templateId'] ?? 0);
			$shortName = (string) ($item['shortName'] ?? '');
			if ($templateId <= 0) {
				++$result['failed'];
				if ($shortName !== '') {
					$result['failedShortNames'][] = $shortName;
				}
				continue;
			}

			$template = \App\Email\Mail::getTemplete($templateId) ?: [];
			$senderRef = \App\Modules\Mail\Models\Module::defaultSenderRefForTemplate($template, $userId);
			if ($senderRef === ''
				|| !\App\Modules\Mail\Models\Module::userCanSendTemplate($userId, $template)) {
				\App\Log\Log::warning(
					'transition mail auto-send aborted: missing or invalid sender (templateId='
					. $templateId . ', senderRef=' . ($senderRef ?: 'empty') . ')',
					'Mail'
				);
				++$result['failed'];
				if ($shortName !== '') {
					$result['failedShortNames'][] = $shortName;
				}
				continue;
			}

			$ok = \App\Email\Mailer::sendFromTemplate([
				'template' => $templateId,
				'moduleName' => 'Candidates',
				'recordId' => $candidateId,
				'field' => $recipient['field'],
				'to' => $recipient['email'],
				'sourceModule' => 'ProjektyRekrutacyjne',
				'sourceRecord' => $projectId,
				'senderRef' => $senderRef,
				'userId' => $userId,
			]);

			if ($ok) {
				++$result['sent'];
			} else {
				++$result['failed'];
				if ($shortName !== '') {
					$result['failedShortNames'][] = $shortName;
				}
			}
		}

		return $result;
	}

	/**
	 * @param list<array{from: string, to: string, templates: list<array{shortName: string, deliveryMode: string}>}> $entries
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
				$templates = $entry['templates'] ?? [];

				if ($from === '' || $to === '' || $from === $to || !\is_array($templates)) {
					continue;
				}

				if (!\in_array($from, $validStatuses, true) || !\in_array($to, $validStatuses, true)) {
					continue;
				}

				$usedShortNames = [];
				foreach ($templates as $template) {
					if (!\is_array($template)) {
						continue;
					}
					$shortName = trim((string) ($template['shortName'] ?? ''));
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
						'delivery_mode' => self::normalizeDeliveryMode((string) ($template['deliveryMode'] ?? '')),
					])->execute();
				}
			}

			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	private static function normalizeDeliveryMode(string $mode): string
	{
		return $mode === self::DELIVERY_AUTO ? self::DELIVERY_AUTO : self::DELIVERY_PROMPT;
	}
}
