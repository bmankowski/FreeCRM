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

namespace App\ModuleManagement\Services;

/**
 * RelationService class.
 * 
 * Service for managing module relationships.
 */
class RelationService
{
	/** @var \App\Db Database instance */
	private $db;

	/**
	 * Constructor.
	 * 
	 * @param \App\Db\Db $db
	 */
	public function __construct(\App\Db\Db $db)
	{
		$this->db = $db;
	}

	/**
	 * Set related list between two modules.
	 * 
	 * @param int $sourceModuleId
	 * @param int $targetModuleId
	 * @param string $label
	 * @param array $actions
	 * @param string $functionName
	 * @return void
	 * @throws \Exception
	 */
	public function setRelatedList(int $sourceModuleId, int $targetModuleId, string $label, array $actions, string $functionName): void
	{
		$transaction = $this->db->beginTransaction();
		try {
			$sourceModule = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($sourceModuleId);
			$targetModule = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($targetModuleId);

			if (!$sourceModule || !$targetModule) {
				throw new \Exception("Source or target module not found");
			}

			if (empty($label)) {
				$label = $targetModule->getName();
			}

			// Check if relation already exists
			$isExists = (new \App\Db\Query())
				->select('relation_id')
				->from('vtiger_relatedlists')
				->where([
					'tabid' => $sourceModuleId,
					'related_tabid' => $targetModuleId,
					'name' => $functionName,
					'label' => $label
				])
				->exists();

			if ($isExists) {
				$transaction->rollBack();
				return;
			}

			// Get next sequence
			$maxSeq = (new \App\Db\Query())
				->from('vtiger_relatedlists')
				->where(['tabid' => $sourceModuleId])
				->max('sequence');
			$sequence = $maxSeq ? $maxSeq + 1 : 0;
			$presence = 0; // 0 - Enabled, 1 - Disabled

			// Determine actions
			if (empty($actions)) {
				$actions = ['ADD'];
			}
			$useactionsText = is_array($actions) ? implode(',', $actions) : $actions;
			$useactionsText = strtoupper($useactionsText);

			// Insert relation
			$this->db->createCommand()->insert('vtiger_relatedlists', [
				'tabid' => $sourceModuleId,
				'related_tabid' => $targetModuleId,
				'name' => $functionName,
				'sequence' => $sequence,
				'label' => $label,
				'presence' => $presence,
				'actions' => $useactionsText
			])->execute();

			// Handle many-to-many table creation
			if ($functionName === 'getManyToMany') {
				$refTableName = \App\Modules\Base\Models\Relation::getReferenceTableInfo($targetModule->getName(), $sourceModule->getName());
				$schema = $this->db->getSchema();
				if (!$schema->getTableSchema($refTableName['table'])) {
					$this->db->createTable($refTableName['table'], [
						'crmid' => 'int',
						'relcrmid' => 'int'
					]);
					$this->db->createCommand()
						->createIndex("{$refTableName['table']}_crmid_idx", $refTableName['table'], 'crmid')
						->execute();
					$this->db->createCommand()
						->createIndex("{$refTableName['table']}_relcrmid_idx", $refTableName['table'], 'relcrmid')
						->execute();
					$this->db->createCommand()->addForeignKey(
						"fk_1_{$refTableName['table']}", 
						$refTableName['table'], 
						'crmid', 
						'vtiger_crmentity', 
						'crmid', 
						'CASCADE', 
						'RESTRICT'
					)->execute();
					$this->db->createCommand()->addForeignKey(
						"fk_2_{$refTableName['table']}", 
						$refTableName['table'], 
						'relcrmid', 
						'vtiger_crmentity', 
						'crmid', 
						'CASCADE', 
						'RESTRICT'
					)->execute();
				}
			}

			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Unset related list between two modules.
	 * 
	 * @param int $sourceModuleId
	 * @param int $targetModuleId
	 * @param string $label
	 * @param string $functionName
	 * @return void
	 * @throws \Exception
	 */
	public function unsetRelatedList(int $sourceModuleId, int $targetModuleId, string $label, string $functionName): void
	{
		$transaction = $this->db->beginTransaction();
		try {
			$targetModule = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($targetModuleId);
			if (!$targetModule) {
				throw new \Exception("Target module not found");
			}

			if (empty($label)) {
				$label = $targetModule->getName();
			}

			$this->db->createCommand()->delete('vtiger_relatedlists', [
				'tabid' => $sourceModuleId,
				'related_tabid' => $targetModuleId,
				'name' => $functionName,
				'label' => $label
			])->execute();

			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}
}
