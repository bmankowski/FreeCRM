<?php
/**
 * FreeCRM - Executes workflows for relation row modifications.
 *
 * @package   FreeCRM
 * @author    bmankowski@gmail.com
 * @license   FreeCRM Public License 1.1
 */

declare(strict_types=1);

namespace App\Modules\Workflow;

class RelationWorkflowRunner
{
	private const ALLOWED_TASK_CLASSES = [
		'VTEmailTask',
		'VTEmailTemplateTask',
		'VTSendNotificationTask',
		'VTEntityMethodTask',
	];

	public static function run(RelationWorkflowContext $context): void
	{
		try {
			$wfm = new VTWorkflowManager();
			$workflows = $wfm->getWorkflowsForModule(
				$context->getSourceModule(),
				VTWorkflowManager::$ON_RELATION_MODIFY
			);
			foreach ($workflows as $workflow) {
				self::runWorkflow($workflow, $context);
			}
		} catch (\Throwable $e) {
			\App\Log\Log::error('RelationWorkflowRunner: ' . $e->getMessage());
		}
	}

	private static function runWorkflow(Workflow $workflow, RelationWorkflowContext $context): void
	{
		$config = \App\Modules\Settings\Workflows\Models\RelationTrigger::getByWorkflowId((int) $workflow->id);
		if (!$config || !self::matchesConfig($config, $context)) {
			return;
		}
		if (!empty($config['once_per_pair']) && self::isActivatedOnce((int) $workflow->id, $context)) {
			return;
		}
		$sourceRecord = $context->getSourceRecordModel();
		$workflow->performTasks($sourceRecord, $context);
		if (!empty($config['once_per_pair'])) {
			self::markActivatedOnce((int) $workflow->id, $context);
		}
	}

	private static function matchesConfig(array $config, RelationWorkflowContext $context): bool
	{
		if ($config['source_module'] !== $context->getSourceModule()) {
			return false;
		}
		if ($config['destination_module'] !== $context->getDestinationModule()) {
			return false;
		}
		$expectedTable = \App\Modules\Settings\Workflows\Models\RelationTrigger::resolveRelationTable(
			$config['source_module'],
			$config['destination_module']
		);
		$expectedField = \App\Modules\Settings\Workflows\Models\RelationTrigger::resolveRelationField(
			$config['source_module'],
			$config['destination_module']
		);
		if ($expectedTable !== $context->getRelationTable()) {
			return false;
		}
		if ($expectedField !== $context->getRelationField()) {
			return false;
		}
		$destinationFilter = $config['destination_value'] ?? '';
		if ($destinationFilter !== '' && $destinationFilter !== $context->getDestinationStatus()) {
			return false;
		}
		$sourceFilter = $config['source_value'] ?? '';
		if ($sourceFilter !== '' && $sourceFilter !== $context->getSourceStatus()) {
			return false;
		}
		return true;
	}

	private static function isActivatedOnce(int $workflowId, RelationWorkflowContext $context): bool
	{
		return (new \App\Db\Query())
			->from('com_vtiger_workflow_relation_activatedonce')
			->where([
				'workflow_id' => $workflowId,
				'source_record_id' => $context->getSourceRecordId(),
				'destination_record_id' => $context->getDestinationRecordId(),
			])
			->exists();
	}

	private static function markActivatedOnce(int $workflowId, RelationWorkflowContext $context): void
	{
		if (self::isActivatedOnce($workflowId, $context)) {
			return;
		}
		\App\Db\Db::getInstance()->createCommand()->insert('com_vtiger_workflow_relation_activatedonce', [
			'workflow_id' => $workflowId,
			'source_record_id' => $context->getSourceRecordId(),
			'destination_record_id' => $context->getDestinationRecordId(),
		])->execute();
	}

	public static function isAllowedTaskClass(string $className): bool
	{
		if (str_contains($className, '\\')) {
			$className = substr(strrchr($className, '\\'), 1);
		}
		return in_array($className, self::ALLOWED_TASK_CLASSES, true);
	}
}
