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

namespace vtlib;

use App\ModuleManagement\ServiceLocator;

/**
 * Field adapter class.
 * 
 * Backward compatibility adapter for vtlib\Field.
 * Delegates to ModuleManagement services.
 * 
 * @deprecated Use App\ModuleManagement\Services\FieldService instead
 */
class Field extends FieldBasic
{
	/**
	 * Get picklist values from table
	 * @return array
	 */
	public function getPicklistValues()
	{
		return \App\Fields\Picklist::getPickListValues($this->name);
	}

	/**
	 * Get owning module name.
	 *
	 * @return string|null
	 */
	public function getModuleName(): ?string
	{
		return $this->tabid ? \App\Module::getModuleName($this->tabid) : null;
	}

	/**
	 * Set values for picklist field (for all the roles)
	 * @param array List of values to add.
	 */
	public function setPicklistValues($values)
	{
		$fieldService = ServiceLocator::getFieldService();
		$fieldService->setPicklistValues($this->id, $values);
	}

	/**
	 * Set relation between field and modules (UIType 10)
	 * @param array List of module names
	 * @return bool
	 */
	public function setRelatedModules($moduleNames)
	{
		$fieldService = ServiceLocator::getFieldService();
		$fieldService->setRelatedModules($this->id, $moduleNames);
		return true;
	}

	/**
	 * Remove relation between the field and modules (UIType 10)
	 * @param array List of module names
	 * @return bool
	 */
	public function unsetRelatedModules($moduleNames)
	{
		$fieldService = ServiceLocator::getFieldService();
		$fieldService->unsetRelatedModules($this->id, $moduleNames);
		return true;
	}

	/**
	 * Get Field instance by fieldid or fieldname
	 * @param string|int $value mixed fieldid or fieldname
	 * @param Module $moduleInstance Instance of the module if fieldname is used
	 * @return self|null
	 */
	public static function getInstance($value, $moduleInstance = false)
	{
		$fieldService = ServiceLocator::getFieldService();
		$module = $moduleInstance ? \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInstance->id) : null;
		$field = $fieldService->getInstance($value, $module);
		
		if (!$field) {
			return null;
		}
		
		$instance = new self();
		$instance->id = $field->getId();
		$instance->name = $field->getName();
		$instance->tabid = $field->getTabid();
		$instance->label = $field->getLabel();
		$instance->table = $field->getTable();
		$instance->column = $field->getColumn();
		$instance->columntype = $field->getColumntype();
		$instance->helpinfo = $field->getHelpinfo();
		$instance->summaryfield = $field->getSummaryfield();
		$instance->header_field = $field->getHeader_field();
		$instance->maxlengthtext = $field->getMaxlengthtext();
		$instance->maxwidthcolumn = $field->getMaxwidthcolumn();
		$instance->masseditable = $field->getMasseditable();
		$instance->uitype = $field->getUitype();
		$instance->typeofdata = $field->getTypeofdata();
		$instance->displaytype = $field->getDisplaytype();
		$instance->generatedtype = $field->getGeneratedtype();
		$instance->readonly = $field->getReadonly();
		$instance->presence = $field->getPresence();
		$instance->defaultvalue = $field->getDefaultvalue();
		$instance->maximumlength = $field->getMaximumlength();
		$instance->sequence = $field->getSequence();
		$instance->quickcreate = $field->getQuickcreate();
		$instance->quicksequence = $field->getQuicksequence();
		$instance->info_type = $field->getInfo_type();
		$instance->fieldparams = $field->getFieldparams();
		
		// Set block if available
		$blockModel = $field->getBlock();
		if ($blockModel instanceof \App\ModuleManagement\Models\Block) {
			$blockInstance = Block::getInstance($blockModel->getId(), $moduleInstance ?: false);
			$instance->block = $blockInstance;
		}
		
		return $instance;
	}

	/**
	 * Get Field instances related to block
	 * @param Block $blockInstance Instance of block to use
	 * @param Module $moduleInstance Instance of module to which block is associated
	 * @return array
	 */
	public static function getAllForBlock($blockInstance, $moduleInstance = null)
	{
		$fieldService = ServiceLocator::getFieldService();
		$moduleModel = $moduleInstance ? \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInstance->id) : null;
		$blockModel = \App\ModuleManagement\ServiceLocator::getBlockService()->getInstance($blockInstance->id, $moduleModel);
		
		$fields = $fieldService->getAllForBlock($blockModel, $moduleModel);
		$instances = [];
		
		foreach ($fields as $field) {
			$instance = new self();
			$instance->id = $field->getId();
			$instance->name = $field->getName();
			$instance->tabid = $field->getTabid();
			$instance->label = $field->getLabel();
			$instance->table = $field->getTable();
			$instance->column = $field->getColumn();
			$instance->columntype = $field->getColumntype();
			$instance->helpinfo = $field->getHelpinfo();
			$instance->summaryfield = $field->getSummaryfield();
			$instance->header_field = $field->getHeader_field();
			$instance->maxlengthtext = $field->getMaxlengthtext();
			$instance->maxwidthcolumn = $field->getMaxwidthcolumn();
			$instance->masseditable = $field->getMasseditable();
			$instance->uitype = $field->getUitype();
			$instance->typeofdata = $field->getTypeofdata();
			$instance->displaytype = $field->getDisplaytype();
			$instance->generatedtype = $field->getGeneratedtype();
			$instance->readonly = $field->getReadonly();
			$instance->presence = $field->getPresence();
			$instance->defaultvalue = $field->getDefaultvalue();
			$instance->maximumlength = $field->getMaximumlength();
			$instance->sequence = $field->getSequence();
			$instance->quickcreate = $field->getQuickcreate();
			$instance->quicksequence = $field->getQuicksequence();
			$instance->info_type = $field->getInfo_type();
			$instance->fieldparams = $field->getFieldparams();
			$instance->block = $blockInstance;
			$instances[] = $instance;
		}
		
		return $instances;
	}

	/**
	 * Get Field instances related to module
	 * @param Module $moduleInstance Instance of module to use
	 * @return array
	 */
	public static function getAllForModule($moduleInstance)
	{
		$fieldService = ServiceLocator::getFieldService();
		$moduleModel = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInstance->id);
		$moduleId = $moduleModel ? $moduleModel->getId() : $moduleInstance->id;
		
		$fields = $fieldService->getAllForModule($moduleId);
		$instances = [];
		
		foreach ($fields as $field) {
			$instance = new self();
			$instance->id = $field->getId();
			$instance->name = $field->getName();
			$instance->tabid = $field->getTabid();
			$instance->label = $field->getLabel();
			$instance->table = $field->getTable();
			$instance->column = $field->getColumn();
			$instance->columntype = $field->getColumntype();
			$instance->helpinfo = $field->getHelpinfo();
			$instance->summaryfield = $field->getSummaryfield();
			$instance->header_field = $field->getHeader_field();
			$instance->maxlengthtext = $field->getMaxlengthtext();
			$instance->maxwidthcolumn = $field->getMaxwidthcolumn();
			$instance->masseditable = $field->getMasseditable();
			$instance->uitype = $field->getUitype();
			$instance->typeofdata = $field->getTypeofdata();
			$instance->displaytype = $field->getDisplaytype();
			$instance->generatedtype = $field->getGeneratedtype();
			$instance->readonly = $field->getReadonly();
			$instance->presence = $field->getPresence();
			$instance->defaultvalue = $field->getDefaultvalue();
			$instance->maximumlength = $field->getMaximumlength();
			$instance->sequence = $field->getSequence();
			$instance->quickcreate = $field->getQuickcreate();
			$instance->quicksequence = $field->getQuicksequence();
			$instance->info_type = $field->getInfo_type();
			$instance->fieldparams = $field->getFieldparams();
			
			// Set block if available
			$blockModel = $field->getBlock();
			if ($blockModel instanceof \App\ModuleManagement\Models\Block) {
			$blockInstance = Block::getInstance($blockModel->getId(), $moduleInstance);
				$instance->block = $blockInstance;
			}
			
			$instances[] = $instance;
		}
		
		return $instances;
	}

	/**
	 * Delete fields associated with the module
	 * @param Module $moduleInstance Instance of module
	 */
	public static function deleteForModule($moduleInstance)
	{
		$fieldService = ServiceLocator::getFieldService();
		$moduleModel = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInstance->id);
		$fieldService->deleteForModule($moduleModel);
		self::log("Deleting fields of the module ... DONE");
	}

	/**
	 * Helper function to log messages
	 * @param string $message Message to log
	 * @param bool $delim true appends linebreak, false to avoid it
	 */
	static function log($message, $delim = true)
	{
		\vtlib\Utils::Log($message, $delim);
	}
}

