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
 * ProfileService class.
 * 
 * Service for profile/permission operations.
 */
class ProfileService
{
	/** @var \App\Db Database instance */
	private $db;

	/**
	 * Constructor.
	 * 
	 * @param \App\Db $db
	 */
	public function __construct(\App\Db $db)
	{
		$this->db = $db;
	}

	/**
	 * Initialize profile setup for a module.
	 * 
	 * @param int $moduleId Module ID
	 * @param bool $isEntityType Whether module is entity type
	 * @return void
	 */
	public function initForModule(int $moduleId, bool $isEntityType): void
	{
		$actionids = (new \App\Db\Query())
			->select(['actionid'])
			->from('vtiger_actionmapping')
			->where(['actionname' => ['Save', 'EditView', 'Delete', 'index', 'DetailView', 'CreateView']])
			->column();

		$profileids = $this->getAllIds();

		foreach ($profileids as $profileid) {
			$this->db->createCommand()->insert('vtiger_profile2tab', [
				'profileid' => $profileid,
				'tabid' => $moduleId,
				'permissions' => 0
			])->execute();

			if ($isEntityType) {
				foreach ($actionids as $actionid) {
					$this->db->createCommand()->insert('vtiger_profile2standardpermissions', [
						'profileid' => $profileid,
						'tabid' => $moduleId,
						'operation' => $actionid,
						'permissions' => 0
					])->execute();
				}
			}
		}
	}

	/**
	 * Initialize profile setup for a field.
	 * 
	 * @param int $moduleId Module ID
	 * @param int $fieldId Field ID
	 * @return void
	 */
	public function initForField(int $moduleId, int $fieldId): void
	{
		// Allow field access to all
		$this->db->createCommand()->insert('vtiger_def_org_field', [
			'tabid' => $moduleId,
			'fieldid' => $fieldId,
			'visible' => 0,
			'readonly' => 0,
		])->execute();

		$profileids = $this->getAllIds();
		$insertedValues = [];

		foreach ($profileids as $profileid) {
			$insertedValues[] = [$profileid, $moduleId, $fieldId, 0, 0];
		}

		if (!empty($insertedValues)) {
			$this->db->createCommand()->batchInsert(
				'vtiger_profile2field',
				['profileid', 'tabid', 'fieldid', 'visible', 'readonly'],
				$insertedValues
			)->execute();
		}
	}

	/**
	 * Delete profile information related with module.
	 * 
	 * @param int $moduleId Module ID
	 * @return void
	 */
	public function deleteForModule(int $moduleId): void
	{
		$this->db->createCommand()->delete('vtiger_def_org_field', ['tabid' => $moduleId])->execute();
		$this->db->createCommand()->delete('vtiger_def_org_share', ['tabid' => $moduleId])->execute();
		$this->db->createCommand()->delete('vtiger_profile2field', ['tabid' => $moduleId])->execute();
		$this->db->createCommand()->delete('vtiger_profile2standardpermissions', ['tabid' => $moduleId])->execute();
		$this->db->createCommand()->delete('vtiger_profile2tab', ['tabid' => $moduleId])->execute();
	}

	/**
	 * Delete profile information related with field.
	 * 
	 * @param int $fieldId Field ID
	 * @return void
	 */
	public function deleteForField(int $fieldId): void
	{
		$this->db->createCommand()->delete('vtiger_def_org_field', ['fieldid' => $fieldId])->execute();
		$this->db->createCommand()->delete('vtiger_profile2field', ['fieldid' => $fieldId])->execute();
	}

	/**
	 * Get all existing profile IDs.
	 * 
	 * @return array Array of profile IDs
	 */
	public function getAllIds(): array
	{
		return (new \App\Db\Query())
			->select(['profileid'])
			->from('vtiger_profile')
			->column();
	}
}

