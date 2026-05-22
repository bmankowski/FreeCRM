<?php
/**
 * FreeCRM - Relation workflow trigger configuration model.
 *
 * @package   FreeCRM
 * @author    bmankowski@gmail.com
 * @license   FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Settings\Workflows\Models;

class RelationTrigger
{
	public const DEFAULT_RELATION_TABLE = 'u_yf_projekty_rekrutacyjne_relations_members_entity';
	public const DEFAULT_RELATION_FIELD = 'recruitment_status_rel';
	public const DEFAULT_SOURCE_MODULE = 'ProjektyRekrutacyjne';
	public const DEFAULT_DESTINATION_MODULE = 'Kandydaci';

	public static function getByWorkflowId(int $workflowId): ?array
	{
		$row = (new \App\Db\Query())
			->from('com_vtiger_workflow_relation_triggers')
			->where(['workflow_id' => $workflowId])
			->one();
		if (!$row) {
			return null;
		}
		return self::normalizeConfig($row, $workflowId);
	}

	/**
	 * Canonical relation table for MVP (ProjektyRekrutacyjne ↔ Kandydaci).
	 * Ignores client-supplied names — must match GetRelatedMembers::TABLE_NAME.
	 */
	public static function resolveRelationTable(string $sourceModule, string $destinationModule): string
	{
		if ($sourceModule === self::DEFAULT_SOURCE_MODULE && $destinationModule === self::DEFAULT_DESTINATION_MODULE) {
			return self::DEFAULT_RELATION_TABLE;
		}
		return self::DEFAULT_RELATION_TABLE;
	}

	public static function resolveRelationField(string $sourceModule, string $destinationModule): string
	{
		if ($sourceModule === self::DEFAULT_SOURCE_MODULE && $destinationModule === self::DEFAULT_DESTINATION_MODULE) {
			return self::DEFAULT_RELATION_FIELD;
		}
		return self::DEFAULT_RELATION_FIELD;
	}

	/**
	 * @param array<string, mixed> $row
	 * @return array<string, mixed>
	 */
	public static function normalizeConfig(array $row, ?int $workflowId = null): array
	{
		$sourceModule = (string) ($row['source_module'] ?? self::DEFAULT_SOURCE_MODULE);
		$destinationModule = (string) ($row['destination_module'] ?? self::DEFAULT_DESTINATION_MODULE);
		$canonicalTable = self::resolveRelationTable($sourceModule, $destinationModule);
		$canonicalField = self::resolveRelationField($sourceModule, $destinationModule);
		if (($row['relation_table'] ?? '') !== $canonicalTable || ($row['relation_field'] ?? '') !== $canonicalField) {
			$row['relation_table'] = $canonicalTable;
			$row['relation_field'] = $canonicalField;
			if ($workflowId !== null) {
				\App\Db\Db::getInstance()->createCommand()->update(
					'com_vtiger_workflow_relation_triggers',
					['relation_table' => $canonicalTable, 'relation_field' => $canonicalField],
					['workflow_id' => $workflowId]
				)->execute();
			}
		}
		return $row;
	}

	public static function saveFromRequest(int $workflowId, \App\Http\Vtiger_Request $request): void
	{
		$sourceModule = $request->get('relation_source_module') ?: self::DEFAULT_SOURCE_MODULE;
		$destinationModule = $request->get('relation_destination_module') ?: self::DEFAULT_DESTINATION_MODULE;
		$data = [
			'workflow_id' => $workflowId,
			'source_module' => $sourceModule,
			'destination_module' => $destinationModule,
			'relation_table' => self::resolveRelationTable($sourceModule, $destinationModule),
			'relation_field' => self::resolveRelationField($sourceModule, $destinationModule),
			'source_value' => $request->get('relation_source_value') ?: null,
			'destination_value' => $request->get('relation_destination_value') ?: '',
			'once_per_pair' => (int) $request->get('relation_once_per_pair'),
		];
		$exists = (new \App\Db\Query())
			->from('com_vtiger_workflow_relation_triggers')
			->where(['workflow_id' => $workflowId])
			->exists();
		if ($exists) {
			unset($data['workflow_id']);
			\App\Db\Db::getInstance()->createCommand()
				->update('com_vtiger_workflow_relation_triggers', $data, ['workflow_id' => $workflowId])
				->execute();
		} else {
			\App\Db\Db::getInstance()->createCommand()
				->insert('com_vtiger_workflow_relation_triggers', $data)
				->execute();
		}
	}

	public static function deleteByWorkflowId(int $workflowId): void
	{
		\App\Db\Db::getInstance()->createCommand()
			->delete('com_vtiger_workflow_relation_triggers', ['workflow_id' => $workflowId])
			->execute();
	}

	/**
	 * Recruitment status picklist values for workflow UI.
	 *
	 * @return array<string, string> value => translated label
	 */
	public static function getRecruitmentStatusOptions(): array
	{
		$values = [
			'PPL_APPLIED',
			'PPL_REJECTED_AFTER_CV',
			'PPL_CANDIDATE_PASSED_SCREENING',
			'PPL_WAITING_FOR_INTERVIEW',
			'PPL_TO_BE_SENT_TO_CLIENT',
			'PPL_SENT_TO_CLIENT',
			'PPL_ACCEPTED',
			'PPL_REJECTED_AFTER_VERIFICATION',
			'PPL_REJECTED_AFTER_INTERVIEW',
			'PPL_OFFER_REJECTED_BY_CANDIDATE',
			'PPL_REJECTED_BY_CLIENT',
		];
		$options = [];
		foreach ($values as $value) {
			$options[$value] = \App\Language::translate($value, 'ProjektyRekrutacyjne');
		}
		return $options;
	}
}
