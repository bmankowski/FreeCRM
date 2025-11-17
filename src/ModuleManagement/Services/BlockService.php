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
 * BlockService class.
 * 
 * Service for managing block operations.
 */
class BlockService
{
	/** @var \App\Db\Db Database instance */
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
	 * Create a new block.
	 * 
	 * @param int $moduleId
	 * @param Models\Block $block
	 * @return int Block ID
	 * @throws \Exception
	 */
	public function create(int $moduleId, Models\Block $block): int
	{
		$transaction = $this->db->beginTransaction();
		try {
			$sequence = $block->getSequence();
			if (!$sequence) {
				$maxSeq = (new \App\Db\Query())
					->from('vtiger_blocks')
					->where(['tabid' => $moduleId])
					->max('sequence');
				$sequence = $maxSeq ? $maxSeq + 1 : 0;
			}

			$display_status = $block->getDisplay_status();
			if ($display_status != 0) {
				$display_status = 1;
			}

			$this->db->createCommand()->insert('vtiger_blocks', [
				'tabid' => $moduleId,
				'blocklabel' => $block->getLabel(),
				'sequence' => $sequence,
				'show_title' => $block->getShowtitle(),
				'visible' => $block->getVisible(),
				'create_view' => $block->getIncreateview(),
				'edit_view' => $block->getIneditview(),
				'detail_view' => $block->getIndetailview(),
				'display_status' => $display_status,
				'iscustom' => $block->getIscustom()
			])->execute();

			$blockId = (int) $this->db->getLastInsertID('vtiger_blocks_blockid_seq');
			$transaction->commit();
			return $blockId;
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Update an existing block.
	 * 
	 * @param int $blockId
	 * @param Models\Block $block
	 * @return void
	 * @throws \Exception
	 */
	public function update(int $blockId, Models\Block $block): void
	{
		$transaction = $this->db->beginTransaction();
		try {
			$this->db->createCommand()->update('vtiger_blocks', [
				'blocklabel' => $block->getLabel(),
				'sequence' => $block->getSequence(),
				'show_title' => $block->getShowtitle(),
				'visible' => $block->getVisible(),
				'create_view' => $block->getIncreateview(),
				'edit_view' => $block->getIneditview(),
				'detail_view' => $block->getIndetailview(),
				'display_status' => $block->getDisplay_status(),
				'iscustom' => $block->getIscustom()
			], ['blockid' => $blockId])->execute();

			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Delete a block.
	 * 
	 * @param int $blockId
	 * @return void
	 * @throws \Exception
	 */
	public function delete(int $blockId): void
	{
		$transaction = $this->db->beginTransaction();
		try {
			$this->db->createCommand()->delete('vtiger_blocks', ['blockid' => $blockId])->execute();
			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Get all blocks for a module.
	 * 
	 * @param int $moduleId
	 * @return array Array of Models\Block
	 */
	public function getAllForModule(int $moduleId): array
	{
		$rows = (new \App\Db\Query())
			->from('vtiger_blocks')
			->where(['tabid' => $moduleId])
			->orderBy(['sequence' => SORT_ASC])
			->all();

		$blocks = [];
		foreach ($rows as $row) {
			$blocks[] = new Models\Block(
				$row['blockid'],
				$row['blocklabel'],
				$row['sequence'],
				$row['show_title'],
				$row['visible'],
				$row['create_view'],
				$row['edit_view'],
				$row['detail_view'],
				$row['display_status'],
				$row['iscustom'],
				null // module - not stored in block
			);
		}

		return $blocks;
	}

	/**
	 * Get single block instance by id or label.
	 *
	 * @param int|string $value
	 * @param Models\Module|null $module
	 *
	 * @return Models\Block|null
	 */
	public function getInstance($value, ?Models\Module $module = null): ?Models\Block
	{
		$query = (new \App\Db\Query())->from('vtiger_blocks');
		if (is_numeric($value)) {
			$query->where(['blockid' => (int) $value]);
		} else {
			if (!$module) {
				return null;
			}
			$query->where(['tabid' => $module->getId(), 'blocklabel' => (string) $value]);
		}
		$row = $query->one();
		if (!$row) {
			return null;
		}

		$block = new Models\Block(
			$row['blockid'],
			$row['blocklabel'],
			$row['sequence'],
			$row['show_title'],
			$row['visible'],
			$row['create_view'],
			$row['edit_view'],
			$row['detail_view'],
			$row['display_status'],
			$row['iscustom'],
			$module
		);

		return $block;
	}

	/**
	 * Delete all blocks for a module.
	 * 
	 * @param int $moduleId Module ID
	 * @param bool $recursive Whether to delete associated fields
	 * @return void
	 */
	public function deleteForModule(int $moduleId, bool $recursive = true): void
	{
		if ($recursive) {
			// Delete fields first
			$fieldService = \App\ModuleManagement\ServiceLocator::getFieldService();
			$fields = (new \App\Db\Query())
				->select(['fieldid'])
				->from('vtiger_field')
				->where(['tabid' => $moduleId])
				->column();
			foreach ($fields as $fieldId) {
				$fieldService->delete($fieldId);
			}
		}

		$this->db->createCommand()
			->delete('vtiger_module_dashboard_blocks', ['tabid' => $moduleId])
			->execute();

		$query = (new \App\Db\Query())
			->select(['blockid'])
			->from('vtiger_blocks')
			->where(['tabid' => $moduleId]);

		$this->db->createCommand()
			->delete('vtiger_blocks_hide', ['blockid' => $query])
			->execute();

		$this->db->createCommand()
			->delete('vtiger_blocks', ['tabid' => $moduleId])
			->execute();
	}
}
