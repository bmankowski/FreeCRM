<?php

namespace App\Modules\Base\Models;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */

class ModuleMeta extends \App\Runtime\BaseModel
{

	public $moduleName = false;
	public $webserviceMeta = false;
	public $user;
	static $_cached_module_meta;

	/**
	 * creates an instance of \App\Modules\Base\Models\ModuleMeta
	 * @param string $name - module name
	 * @param <Object> $user - Users Object
	 * @return \App\Modules\Base\Models\ModuleMeta
	 */
	public static function getInstance($name, $user)
	{
		$self = new \App\Modules\Base\Models\ModuleMeta();
		$self->moduleName = $name;
		$self->user = $user;

		$userId = method_exists($user, 'getId') ? $user->getId() : (method_exists($user, 'get') ? $user->get('id') : $user->id);
		if (!empty(self::$_cached_module_meta[$name][$userId])) {
			$self->webserviceMeta = self::$_cached_module_meta[$name][$userId];
			return $self;
		}

		$handler = vtws_getModuleHandlerFromName($self->moduleName, $user);
		$self->webserviceMeta = $handler->getMeta();
		self::$_cached_module_meta[$name][$userId] = $self->webserviceMeta;
		return $self;
	}

	/**
	 * Functions returns webservices meta object
	 * @return webservices meta
	 */
	public function getMeta()
	{
		return $this->webserviceMeta;
	}

	/**
	 * Function returns list of fields based on type
	 * @param <type> $type
	 * @return mixed
	 */
	public function getFieldListByType($type)
	{
		$meta = $this->getMeta();
		return $meta->getFieldListByType($type);
	}

	/**
	 * Function returns accessible fields in a module
	 *  \App\Modules\Base\Models\Field[]
	 */
	public function getAccessibleFields($blocks = false)
	{
		$userId = method_exists($this->user, 'getId') ? $this->user->getId() : (method_exists($this->user, 'get') ? $this->user->get('id') : $this->user->id);
		
		// Ensure meta is initialized
		if (empty(self::$_cached_module_meta[$this->moduleName][$userId])) {
			// Initialize meta if not cached
			$handler = vtws_getModuleHandlerFromName($this->moduleName, $this->user);
			self::$_cached_module_meta[$this->moduleName][$userId] = $handler->getMeta();
		}
		
		$meta = self::$_cached_module_meta[$this->moduleName][$userId];
		if ($meta === null) {
			throw new \App\Exceptions\AppException("Module meta not found for module: {$this->moduleName}");
		}
		$moduleFields = $meta->getModuleFields();
		$accessibleFields = [];
		foreach ($moduleFields as $fieldName => $fieldInstance) {
			if ($fieldInstance->getPresence() !== 1) {
				if ($blocks) {
					$blockName = $fieldInstance->getBlockName();
					if (empty($blockName)) {
						$blockName = 'LBL_NOT_ASSIGNET_TO_BLOCK';
					}
					$accessibleFields[$blockName][$fieldName] = $fieldInstance;
				} else {
					$accessibleFields[$fieldName] = $fieldInstance;
				}
			}
		}
		return $accessibleFields;
	}

	/**
	 * Function returns mergable fields in the module
	 * @return <Array of Vtiger_field>
	 */
	public function getMergableFields($blocks = false)
	{
		$accessibleFields = $this->getAccessibleFields($blocks);
		$mergableFields = [];
		if ($blocks) {
			foreach ($accessibleFields as $block => $fields) {
				foreach ($fields as $fieldName => $fieldInstance) {
					if ($fieldInstance->getFieldDataType() == 'reference') {
						$referencedModules = $fieldInstance->getReferenceList();
						if ($referencedModules[0] == 'Users') {
							continue;
						}
					}
					$mergableFields[$block][$fieldName] = $fieldInstance;
				}
			}
		} else {
			foreach ($accessibleFields as $fieldName => $fieldInstance) {
				// We need to avoid Last Modified by or any such User reference field
				// for now as Query Generator is not handling it well enough.
				// The case in which query generator is failing to generate right query is,
				// Assigned User field is not there either in the selected fields list or in the conditions
				// and condition is added on the User reference field
				if ($fieldInstance->getFieldDataType() == 'reference') {
					$referencedModules = $fieldInstance->getReferenceList();
					if ($referencedModules[0] == 'Users') {
						continue;
					}
				}
				$mergableFields[$fieldName] = $fieldInstance;
			}
		}
		return $mergableFields;
	}

	/**
	 * Function returns mandatory importable fields
	 *  \App\Modules\Base\Models\Field[]
	 */
	public function getMandatoryImportableFields()
	{

		$focus = \App\Core\CRMEntity::getInstance($this->moduleName);
		if (method_exists($focus, 'getMandatoryImportableFields')) {
			$mandatoryFields = $focus->getMandatoryImportableFields();
		} else {
			$moduleFields = $this->getAccessibleFields();
			$mandatoryFields = [];
			foreach ($moduleFields as $fieldName => $fieldInstance) {
				if ($fieldInstance->isMandatory() && $fieldInstance->getFieldDataType() != 'owner' && $this->isEditableField($fieldInstance)) {
					$mandatoryFields[$fieldName] = $fieldInstance->getFieldLabelKey();
				}
			}
		}
		return $mandatoryFields;
	}

	/**
	 * Function returns importable fields
	 *  \App\Modules\Base\Models\Field[]
	 */
	public function getImportableFields($blocks = false)
	{
		$focus = \App\Core\CRMEntity::getInstance($this->moduleName);
		if (method_exists($focus, 'getImportableFields')) {
			$importableFields = $focus->getImportableFields();
		} else {
			$moduleFields = $this->getAccessibleFields($blocks);
			$importableFields = [];
			if ($blocks) {
				foreach ($moduleFields as $blockName => $fields) {
					foreach ($fields as $fieldName => $fieldInstance) {
						if ($fieldInstance->getTableName() != 'vtiger_entity_stats' && ($this->isEditableField($fieldInstance) && ($fieldInstance->getTableName() != 'vtiger_crmentity' || $fieldInstance->getColumnName() != 'modifiedby')
							) || ($fieldInstance->getUIType() == '70' && $fieldName != 'modifiedtime')) {
							$importableFields[$blockName][$fieldName] = $fieldInstance;
						}
					}
				}
			} else {
				foreach ($moduleFields as $fieldName => $fieldInstance) {
					if (($this->isEditableField($fieldInstance) && ($fieldInstance->getTableName() != 'vtiger_crmentity' || $fieldInstance->getColumnName() != 'modifiedby')
						) || ($fieldInstance->getUIType() == '70' && $fieldName != 'modifiedtime')) {
						$importableFields[$fieldName] = $fieldInstance;
					}
				}
			}
		}
		return $importableFields;
	}

	/**
	 * Function returns Entity Name fields
	 *  \App\Modules\Base\Models\Field[]
	 */
	public function getEntityFields()
	{
		$moduleFields = $this->getAccessibleFields();
		$entityColumnNames = vtws_getEntityNameFields($this->moduleName);
		$entityNameFields = [];
		foreach ($moduleFields as $fieldName => $fieldInstance) {
			$fieldColumnName = $fieldInstance->getColumnName();
			if (in_array($fieldColumnName, $entityColumnNames)) {
				$entityNameFields[$fieldName] = $fieldInstance;
			}
		}
		return $entityNameFields;
	}

	/**
	 * Function checks if the field is editable
	 * @param mixed $fieldInstance
	 * @return boolean
	 */
	public function isEditableField($fieldInstance)
	{
		if (((int) $fieldInstance->getDisplayType()) === 2 ||
			in_array($fieldInstance->getPresence(), array(1, 3)) ||
			strcasecmp($fieldInstance->getFieldDataType(), "autogenerated") === 0 ||
			strcasecmp($fieldInstance->getFieldDataType(), "id") === 0 ||
			$fieldInstance->isReadOnly() === true ||
			$fieldInstance->getUIType() == 70 ||
			$fieldInstance->getUIType() == 4) {

			return false;
		}
		return true;
	}

	/**
	 * Function returns list of mandatory fields
	 *  \App\Modules\Base\Models\Field[]
	 */
	public function getMandatoryFields()
	{
		$focus = \App\Core\CRMEntity::getInstance($this->moduleName);
		if (method_exists($focus, 'getMandatoryImportableFields')) {
			$mandatoryFields = $focus->getMandatoryImportableFields();
		} else {
			$moduleFields = $this->getAccessibleFields();
			$mandatoryFields = [];
			foreach ($moduleFields as $fieldName => $fieldInstance) {
				if ($fieldInstance->isMandatory() && $fieldInstance->getFieldDataType() != 'owner' && $this->isEditableField($fieldInstance)) {
					$mandatoryFields[$fieldName] = \App\Runtime\Vtiger_Language_Handler::translate($fieldInstance->getFieldLabelKey(), $this->moduleName);
				}
			}
		}
		return $mandatoryFields;
	}
}
