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

use App\ModuleManagement\Models;

/**
 * FieldService class.
 * 
 * Service for managing field operations.
 */
class FieldService
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
	 * Create a new field.
	 * 
	 * @param int $moduleId
	 * @param int $blockId
	 * @param Models\Field $field
	 * @return int Field ID
	 * @throws \Exception
	 */
	public function create(int $moduleId, int $blockId, Models\Field $field): int
	{
		$transaction = $this->db->beginTransaction();
		try {
			$module = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleId);
			if (!$module) {
				throw new \Exception("Module with ID $moduleId not found");
			}

			$fieldId = $this->db->getUniqueID('vtiger_field');
			
			// Determine sequence
			$sequence = $field->getSequence();
			if (!$sequence) {
				$maxSeq = (new \App\Db\Query())
					->from('vtiger_field')
					->where(['tabid' => $moduleId, 'block' => $blockId])
					->max('sequence');
				$sequence = $maxSeq ? $maxSeq + 1 : 0;
			}

			// Determine quicksequence
			$quicksequence = null;
			if ($field->getQuickcreate() != 1) {
				$quicksequence = $field->getQuicksequence();
				if (!$quicksequence) {
					$maxSeq = (new \App\Db\Query())
						->from('vtiger_field')
						->where(['tabid' => $moduleId])
						->max('quickcreatesequence');
					$quicksequence = $maxSeq ? $maxSeq + 1 : 0;
				}
			}

			// Set defaults
			$table = $field->getTable() ?: $module->getBasetable();
			$column = $field->getColumn() ?: strtolower($field->getName());
			$columntype = $field->getColumntype() ?: 'string(100)';
			$label = $field->getLabel() ?: $field->getName();

			// Insert field
			$this->db->createCommand()->insert('vtiger_field', [
				'tabid' => $moduleId,
				'fieldid' => $fieldId,
				'columnname' => $column,
				'tablename' => $table,
				'generatedtype' => (int) $field->getGeneratedtype(),
				'uitype' => $field->getUitype(),
				'fieldname' => $field->getName(),
				'fieldlabel' => $label,
				'readonly' => $field->getReadonly(),
				'presence' => $field->getPresence(),
				'defaultvalue' => $field->getDefaultvalue(),
				'maximumlength' => $field->getMaximumlength(),
				'sequence' => $sequence,
				'block' => $blockId,
				'displaytype' => $field->getDisplaytype(),
				'typeofdata' => $field->getTypeofdata(),
				'quickcreate' => (int) $field->getQuickcreate(),
				'quickcreatesequence' => $quicksequence ? (int) $quicksequence : null,
				'info_type' => $field->getInfo_type(),
				'helpinfo' => $field->getHelpinfo(),
				'summaryfield' => (int) $field->getSummaryfield(),
				'fieldparams' => $field->getFieldparams(),
				'masseditable' => $field->getMasseditable(),
			])->execute();

			// Initialize profile
			$this->initProfile($moduleId, $fieldId);

			// Add column to table
			if ($columntype) {
				$tableSchema = $this->db->getSchema()->getTableSchema($table, true);
				if (is_null($tableSchema->getColumn($column))) {
					if (is_array($columntype)) {
						$columntype = $this->db->getSchema()->createColumnSchemaBuilder($columntype[0], $columntype[1]);
					}
					$this->db->createCommand()->addColumn($table, $column, $columntype)->execute();
				}
				if ($field->getUitype() === 10) {
					$this->db->createCommand()
						->createIndex("{$table}_{$column}_idx", $table, $column)
						->execute();
				}
			}

			$transaction->commit();
			return $fieldId;
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Update an existing field.
	 * 
	 * @param int $fieldId
	 * @param Models\Field $field
	 * @return void
	 * @throws \Exception
	 */
	public function update(int $fieldId, Models\Field $field): void
	{
		$transaction = $this->db->beginTransaction();
		try {
			$label = $field->getLabel() ?: $field->getName();
			
			$this->db->createCommand()->update('vtiger_field', [
				'columnname' => $field->getColumn(),
				'tablename' => $field->getTable(),
				'generatedtype' => (int) $field->getGeneratedtype(),
				'uitype' => $field->getUitype(),
				'fieldname' => $field->getName(),
				'fieldlabel' => $label,
				'readonly' => $field->getReadonly(),
				'presence' => $field->getPresence(),
				'defaultvalue' => $field->getDefaultvalue(),
				'maximumlength' => $field->getMaximumlength(),
				'sequence' => $field->getSequence(),
				'displaytype' => $field->getDisplaytype(),
				'typeofdata' => $field->getTypeofdata(),
				'quickcreate' => (int) $field->getQuickcreate(),
				'quickcreatesequence' => $field->getQuicksequence() ? (int) $field->getQuicksequence() : null,
				'info_type' => $field->getInfo_type(),
				'helpinfo' => $field->getHelpinfo(),
				'summaryfield' => (int) $field->getSummaryfield(),
				'fieldparams' => $field->getFieldparams(),
				'masseditable' => $field->getMasseditable(),
			], ['fieldid' => $fieldId])->execute();

			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Delete a field.
	 * 
	 * @param int $fieldId
	 * @return void
	 * @throws \Exception
	 */
	public function delete(int $fieldId): void
	{
		$transaction = $this->db->beginTransaction();
		try {
			// Delete profile
			\App\ModuleManagement\ServiceLocator::getProfileService()->deleteForField($fieldId);

			// Delete field
			$this->db->createCommand()->delete('vtiger_field', ['fieldid' => $fieldId])->execute();

			// Delete fieldmodulerel if uitype=10
			$field = $this->getInstance($fieldId);
			if ($field && $field->getUitype() === 10) {
				$this->db->createCommand()
					->delete('vtiger_fieldmodulerel', ['fieldid' => $fieldId])
					->execute();
			}

			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Get field instance by ID or name.
	 * 
	 * @param string|int $fieldIdOrName
	 * @param Models\Module|null $module
	 * @return Models\Field|null
	 */
	public function getInstance($fieldIdOrName, ?Models\Module $module = null): ?Models\Field
	{
		$moduleid = $module ? $module->getId() : null;
		$data = \App\Fields\Field::getFieldInfo($fieldIdOrName, $moduleid);
		if (!$data) {
			return null;
		}

		// Get block instance if needed
		$block = null;
		if (isset($data['block'])) {
			$blockService = \App\ModuleManagement\ServiceLocator::getBlockService();
			$allBlocks = $blockService->getAllForModule($data['tabid']);
			// Find the block with matching ID
			foreach ($allBlocks as $b) {
				if ($b->getId() == $data['block']) {
					$block = $b;
					break;
				}
			}
		}

		return new Models\Field(
			$data['fieldid'],
			$data['fieldname'],
			$data['tabid'],
			$data['fieldlabel'],
			$data['tablename'],
			$data['columnname'],
			null, // columntype - not in getFieldInfo
			$data['helpinfo'] ?? '',
			$data['summaryfield'] ?? 0,
			$data['header_field'] ?? false,
			$data['maxlengthtext'] ?? 0,
			$data['maxwidthcolumn'] ?? 0,
			$data['masseditable'] ?? 1,
			$data['uitype'],
			$data['typeofdata'],
			$data['displaytype'] ?? 1,
			$data['generatedtype'] ?? 1,
			$data['readonly'] ?? 1,
			$data['presence'] ?? 2,
			$data['defaultvalue'] ?? '',
			$data['maximumlength'] ?? 100,
			$data['sequence'] ?? false,
			$data['quickcreate'] ?? 1,
			$data['quickcreatesequence'] ?? false,
			$data['info_type'] ?? 'BAS',
			$block,
			$data['fieldparams'] ?? ''
		);
	}

	/**
	 * Set picklist values for a field.
	 * 
	 * @param int $fieldId
	 * @param array $values
	 * @return void
	 * @throws \Exception
	 */
	public function setPicklistValues(int $fieldId, array $values): void
	{
		$field = $this->getInstance($fieldId);
		if (!$field) {
			throw new \Exception("Field with ID $fieldId not found");
		}

		// Non-Role based picklist values (uitype=16)
		if ($field->getUitype() === 16) {
			$this->setNoRolePicklistValues($fieldId, $field, $values);
			return;
		}

		$db = $this->db;
		$picklistTable = 'vtiger_' . $field->getName();
		$picklistIdCol = $field->getName() . 'id';

		// Create picklist table if not exists
		if (!$db->isTableExists($picklistTable)) {
			$importer = new \App\Db\Importers\Base();
			$db->createTable($picklistTable, [
				$picklistIdCol => 'pk',
				$field->getName() => 'string',
				'presence' => $importer->boolean()->defaultValue(true),
				'picklist_valueid' => $importer->smallInteger()->defaultValue(0),
				'sortorderid' => $importer->smallInteger()->defaultValue(0)
			]);
			$db->createCommand()->insert('vtiger_picklist', ['name' => $field->getName()])->execute();
			$newPicklistId = $db->getLastInsertID('vtiger_picklist_picklistid_seq');
		} else {
			$newPicklistId = (new \App\Db\Query())
				->select(['picklistid'])
				->from('vtiger_picklist')
				->where(['name' => $field->getName()])
				->scalar();
		}

		// Handle special column names
		$specialNameSpacedPicklists = [
			'opportunity_type' => 'opptypeid',
			'duration_minutes' => 'minutesid',
		];
		$fieldName = (string) $field->getName();
		if ($db->getTableSchema($picklistTable, true)->getColumn($fieldName . '_id')) {
			$picklistIdCol = $fieldName . '_id';
		} elseif (array_key_exists($fieldName, $specialNameSpacedPicklists)) {
			$picklistIdCol = $specialNameSpacedPicklists[$fieldName];
		}

		// Get existing picklist values
		$picklistValues = \App\Fields\Picklist::getPickListValues($field->getName());

		// Add new values
		$sortid = 0;
		foreach ($values as $value) {
			if (in_array($value, $picklistValues)) {
				continue;
			}
			$newPicklistValueId = $db->getUniqueID('vtiger_picklistvalues');
			$presence = 1; // 0 - readonly
			++$sortid;
			$db->createCommand()->insert($picklistTable, [
				$field->getName() => $value,
				'presence' => $presence,
				'picklist_valueid' => $newPicklistValueId,
				'sortorderid' => $sortid
			])->execute();

			// Associate with all roles
			$roleIds = (new \App\Db\Query)->select('roleid')->from('vtiger_role')->column();
			$insertedData = [];
			foreach ($roleIds as $roleId) {
				$insertedData[] = [$roleId, $newPicklistValueId, $newPicklistId, $sortid];
			}
			$db->createCommand()
				->batchInsert('vtiger_role2picklist', ['roleid', 'picklistvalueid', 'picklistid', 'sortid'], $insertedData)
				->execute();
		}
	}

	/**
	 * Set non-role based picklist values.
	 * 
	 * @param int $fieldId
	 * @param Models\Field $field
	 * @param array $values
	 * @return void
	 */
	private function setNoRolePicklistValues(int $fieldId, Models\Field $field, array $values): void
	{
		$db = $this->db;
		$pickListNameIDs = ['recurring_frequency', 'payment_duration'];
		$picklistTable = 'vtiger_' . $field->getName();
		$picklistIdCol = $field->getName() . 'id';
		if (in_array($field->getName(), $pickListNameIDs)) {
			$picklistIdCol = $field->getName() . '_id';
		}

		if (!$db->isTableExists($picklistTable)) {
			$importer = new \App\Db\Importers\Base();
			$db->createTable($picklistTable, [
				$picklistIdCol => 'pk',
				$field->getName() => 'string',
				'presence' => $importer->boolean()->defaultValue(true),
				'sortorderid' => $importer->smallInteger()->defaultValue(0)
			]);
		}

		$picklistValues = \App\Fields\Picklist::getPickListValues($field->getName());
		$sortid = 1;
		foreach ($values as $value) {
			if (in_array($value, $picklistValues)) {
				continue;
			}
			$presence = 1;
			$db->createCommand()->insert($picklistTable, [
				$field->getName() => $value,
				'sortorderid' => $sortid,
				'presence' => $presence,
			])->execute();
			$sortid = $sortid + 1;
		}
	}

	/**
	 * Get all fields for a module.
	 *
	 * @param int $moduleId
	 *
	 * @return Models\Field[]
	 */
	public function getAllForModule(int $moduleId): array
	{
		$dataReader = (new \App\Db\Query())
			->from('vtiger_field')
			->where(['tabid' => $moduleId])
			->orderBy(['block' => SORT_ASC, 'sequence' => SORT_ASC])
			->createCommand()
			->query();

		$blockService = \App\ModuleManagement\ServiceLocator::getBlockService();
		$blocks = $blockService->getAllForModule($moduleId);
		$blocksById = [];
		foreach ($blocks as $blockModel) {
			$blocksById[$blockModel->getId()] = $blockModel;
		}

		$fields = [];
		while ($row = $dataReader->read()) {
			$block = null;
			if (isset($row['block']) && isset($blocksById[$row['block']])) {
				$block = $blocksById[$row['block']];
			}
			$fields[] = new Models\Field(
				$row['fieldid'],
				$row['fieldname'],
				$row['tabid'],
				$row['fieldlabel'],
				$row['tablename'],
				$row['columnname'],
				null,
				$row['helpinfo'] ?? '',
				$row['summaryfield'] ?? 0,
				$row['header_field'] ?? false,
				$row['maxlengthtext'] ?? 0,
				$row['maxwidthcolumn'] ?? 0,
				$row['masseditable'] ?? 1,
				$row['uitype'],
				$row['typeofdata'],
				$row['displaytype'] ?? 1,
				$row['generatedtype'] ?? 1,
				$row['readonly'] ?? 1,
				$row['presence'] ?? 2,
				$row['defaultvalue'] ?? '',
				$row['maximumlength'] ?? 100,
				$row['sequence'] ?? false,
				$row['quickcreate'] ?? 1,
				$row['quickcreatesequence'] ?? false,
				$row['info_type'] ?? 'BAS',
				$block,
				$row['fieldparams'] ?? ''
			);
		}

		return $fields;
	}

	/**
	 * Set related modules for a field (UIType 10).
	 * 
	 * @param int $fieldId
	 * @param array $moduleNames
	 * @return void
	 * @throws \Exception
	 */
	public function setRelatedModules(int $fieldId, array $moduleNames): void
	{
		if (count($moduleNames) == 0) {
			throw new \Exception('No module names provided');
		}

		$field = $this->getInstance($fieldId);
		if (!$field) {
			throw new \Exception("Field with ID $fieldId not found");
		}

		$module = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($field->getTabid());
		if (!$module) {
			throw new \Exception("Module not found for field");
		}

		$moduleName = $module->getName();

		foreach ($moduleNames as $relmodule) {
			$checkRes = (new \App\Db\Query())
				->from('vtiger_fieldmodulerel')
				->where(['fieldid' => $fieldId, 'module' => $moduleName, 'relmodule' => $relmodule])
				->one();

			if ($checkRes) {
				continue;
			}

			$this->db->createCommand()->insert('vtiger_fieldmodulerel', [
				'fieldid' => $fieldId,
				'module' => $moduleName,
				'relmodule' => $relmodule
			])->execute();
		}
	}

	/**
	 * Initialize profile for field.
	 * 
	 * @param int $moduleId
	 * @param int $fieldId
	 * @return void
	 */
	private function initProfile(int $moduleId, int $fieldId): void
	{
		\App\ModuleManagement\ServiceLocator::getProfileService()->initForField($moduleId, $fieldId);
	}

	/**
	 * Create vtlib Field instance for compatibility.
	 * 
	 * @param int $fieldId
	 * @return \vtlib\Field
	 */
	private function createVtlibFieldInstance(int $fieldId): \vtlib\Field
	{
		$field = $this->getInstance($fieldId);
		if (!$field) {
			throw new \Exception("Field with ID $fieldId not found");
		}

		$vtlibField = new \vtlib\Field();
		$vtlibField->id = $field->getId();
		$vtlibField->name = $field->getName();
		$vtlibField->tabid = $field->getTabid();
		$vtlibField->label = $field->getLabel();
		$vtlibField->table = $field->getTable();
		$vtlibField->column = $field->getColumn();
		$vtlibField->columntype = $field->getColumntype();
		$vtlibField->helpinfo = $field->getHelpinfo();
		$vtlibField->summaryfield = $field->getSummaryfield();
		$vtlibField->header_field = $field->getHeader_field();
		$vtlibField->maxlengthtext = $field->getMaxlengthtext();
		$vtlibField->maxwidthcolumn = $field->getMaxwidthcolumn();
		$vtlibField->masseditable = $field->getMasseditable();
		$vtlibField->uitype = $field->getUitype();
		$vtlibField->typeofdata = $field->getTypeofdata();
		$vtlibField->displaytype = $field->getDisplaytype();
		$vtlibField->generatedtype = $field->getGeneratedtype();
		$vtlibField->readonly = $field->getReadonly();
		$vtlibField->presence = $field->getPresence();
		$vtlibField->defaultvalue = $field->getDefaultvalue();
		$vtlibField->maximumlength = $field->getMaximumlength();
		$vtlibField->sequence = $field->getSequence();
		$vtlibField->quickcreate = $field->getQuickcreate();
		$vtlibField->quicksequence = $field->getQuicksequence();
		$vtlibField->info_type = $field->getInfo_type();
		$vtlibField->fieldparams = $field->getFieldparams();
		
		// Set block if available
		$block = $field->getBlock();
		if ($block instanceof Models\Block) {
			$vtlibBlock = new \vtlib\Block();
			$vtlibBlock->id = $block->getId();
			$vtlibBlock->label = $block->getLabel();
			$vtlibBlock->sequence = $block->getSequence();
			$vtlibBlock->showtitle = $block->getShowtitle();
			$vtlibBlock->visible = $block->getVisible();
			$vtlibBlock->increateview = $block->getIncreateview();
			$vtlibBlock->ineditview = $block->getIneditview();
			$vtlibBlock->indetailview = $block->getIndetailview();
			$vtlibBlock->display_status = $block->getDisplay_status();
			$vtlibBlock->iscustom = $block->getIscustom();
			$vtlibField->block = $vtlibBlock;
		} elseif ($block) {
			$vtlibField->block = $block; // Assume it's already a vtlib Block or ID
		}
		
		return $vtlibField;
	}
}
