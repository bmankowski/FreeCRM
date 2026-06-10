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
 * Block adapter class.
 * 
 * Backward compatibility adapter for vtlib\Block.
 * Delegates to ModuleManagement services.
 * 
 * @deprecated Use App\ModuleManagement\Services\BlockService instead
 */
class Block
{
	/** @var int ID of this block instance */
	public $id;

	/** @var string Label for this block instance */
	public $label;
	
	/** @var int Sequence */
	public $sequence;
	
	/** @var int Show title */
	public $showtitle = 0;
	
	/** @var int Visible */
	public $visible = 0;
	
	/** @var int In create view */
	public $increateview = 0;
	
	/** @var int In edit view */
	public $ineditview = 0;
	
	/** @var int In detail view */
	public $indetailview = 0;
	
	/** @var int Display status */
	public $display_status = 1;
	
	/** @var int Is custom */
	public $iscustom = 0;
	
	/** @var Module Module instance */
	public $module;

	/** @var string Basic table name */
	public static $baseTable = 'vtiger_blocks';

	/**
	 * Get next sequence value to use for this block instance
	 * @return int
	 */
	public function __getNextSequence()
	{
		return (new \App\Db\Query())->from(self::$baseTable)->where(['tabid' => $this->module->id])->max('sequence') + 1;
	}

	/**
	 * Initialize this block instance
	 * @param array Map of column name and value
	 * @param Module Module Instance of module to which this block is associated
	 */
	public function initialize($valuemap, $moduleInstance = false)
	{
		$this->id = isset($valuemap['blockid']) ? $valuemap['blockid'] : null;
		$this->label = isset($valuemap['blocklabel']) ? $valuemap['blocklabel'] : null;
		$this->display_status = isset($valuemap['display_status']) ? $valuemap['display_status'] : null;
		$this->sequence = isset($valuemap['sequence']) ? $valuemap['sequence'] : null;
		$this->iscustom = isset($valuemap['iscustom']) ? $valuemap['iscustom'] : null;
		$tabid = isset($valuemap['tabid']) ? $valuemap['tabid'] : null;
		$this->module = $moduleInstance ? $moduleInstance : Module::getInstance($tabid);
	}

	/**
	 * Create vtiger CRM block
	 * @param Module $moduleInstance
	 */
	public function __create($moduleInstance)
	{
		$blockService = ServiceLocator::getBlockService();
		$moduleModel = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInstance->id);
		
		$blockModel = new \App\ModuleManagement\Models\Block(
			false,
			$this->label,
			$this->sequence,
			$this->showtitle,
			$this->visible,
			$this->increateview,
			$this->ineditview,
			$this->indetailview,
			$this->display_status,
			$this->iscustom
		);
		
		$this->id = $blockService->create($moduleModel->getId(), $blockModel);
		$this->module = $moduleInstance;
		self::log("Creating Block $this->label ... DONE");
	}

	public function __update()
	{
		self::log("Updating Block $this->label ... DONE");
	}

	/**
	 * Delete this instance
	 */
	public function __delete()
	{
		$blockService = ServiceLocator::getBlockService();
		$blockService->delete($this->id);
		self::log("Deleting Block $this->label ... DONE");
	}

	/**
	 * Save this block instance
	 * @param Module Instance of the module to which this block is associated
	 * @return int Block ID
	 */
	public function save($moduleInstance = false)
	{
		if ($this->id) {
			$this->__update();
		} else {
			$this->__create($moduleInstance);
		}

		$moduleName = null;
		if ($moduleInstance instanceof \App\Modules\Base\Models\Module) {
			$moduleName = $moduleInstance->getName();
		} elseif ($moduleInstance instanceof \vtlib\Module && isset($moduleInstance->name)) {
			$moduleName = $moduleInstance->name;
		} elseif ($this->module instanceof \App\Modules\Base\Models\Module) {
			$moduleName = $this->module->getName();
		} elseif ($this->module instanceof \vtlib\Module && isset($this->module->name)) {
			$moduleName = $this->module->name;
		} elseif ($this->module && is_string($this->module)) {
			$moduleName = $this->module;
		} elseif (isset($this->module->id)) {
			$moduleName = \App\Utils\ModuleUtils::getModuleName((int) $this->module->id);
		}
		if ($moduleName) {
			\App\Cache\Cache::delete('ModuleBlock', $moduleName);
		}
		return $this->id;
	}

	/**
	 * Delete block instance
	 * @param bool True to delete associated fields, False to avoid it
	 */
	public function delete($recursive = true)
	{
		if ($recursive) {
			$fields = \App\Fields\Field::getAllForBlock($this);
			foreach ($fields as $fieldInstance)
				$fieldInstance->delete();
		}
		$this->__delete();

		$moduleName = null;
		if ($this->module instanceof \App\Modules\Base\Models\Module) {
			$moduleName = $this->module->getName();
		} elseif ($this->module instanceof \vtlib\Module && isset($this->module->name)) {
			$moduleName = $this->module->name;
		} elseif (isset($this->module->id)) {
			$moduleName = \App\Utils\ModuleUtils::getModuleName((int) $this->module->id);
		}
		if (!$moduleName && isset($this->tabid)) {
			$moduleName = \App\Utils\ModuleUtils::getModuleName((int) $this->tabid);
		}
		if ($moduleName) {
			\App\Cache\Cache::delete('ModuleBlock', $moduleName);
		}
	}

	/**
	 * Add field to this block
	 * @param Field Instance of field to add to this block.
	 * @return Reference to this block instance
	 */
	public function addField($fieldInstance)
	{
		$moduleId = null;
		if (isset($fieldInstance->tabid) && $fieldInstance->tabid) {
			$moduleId = (int) $fieldInstance->tabid;
		}
		if (!$moduleId && isset($this->module)) {
			if (is_object($this->module)) {
				if (isset($this->module->id)) {
					$moduleId = (int) $this->module->id;
				} elseif (method_exists($this->module, 'getId')) {
					$moduleId = (int) $this->module->getId();
				} elseif (isset($this->module->name)) {
					$moduleId = (int) \App\Utils\ModuleUtils::getModuleId($this->module->name);
				}
			} elseif (is_numeric($this->module)) {
				$moduleId = (int) $this->module;
			}
		}
		if (!$moduleId && method_exists($fieldInstance, 'getModuleName')) {
			$moduleName = $fieldInstance->getModuleName();
			if ($moduleName) {
				$moduleId = (int) \App\Utils\ModuleUtils::getModuleId($moduleName);
			}
		}
		if (!$moduleId) {
			throw new \RuntimeException('Unable to determine module id for block field creation');
		}

		$blockId = (int) $this->id;
		if (!$blockId) {
			throw new \RuntimeException('Unable to determine block id for field creation');
		}

		// If field already has an ID, it already exists — update via save()
		if (isset($fieldInstance->id) && $fieldInstance->id) {
			$fieldModel = \App\Modules\Base\Models\Field::getInstance((int) $fieldInstance->id);
			if ($fieldModel) {
				$fieldModel->save();
			}
			return $this;
		}

		$get = function ($object, $property, $fallback = null) {
			if (is_object($object)) {
				if (isset($object->$property)) {
					return $object->$property;
				}
				$method = 'get' . str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
				if (method_exists($object, $method)) {
					return $object->$method();
				}
				if (method_exists($object, 'get')) {
					$viaGet = $object->get($property);
					if ($viaGet !== null) {
						return $viaGet;
					}
				}
			}
			return $fallback;
		};

		$moduleService = ServiceLocator::getModuleService();
		$moduleModel = $moduleService->getInstance($moduleId);
		if (!$moduleModel) {
			throw new \RuntimeException("Module with ID {$moduleId} not found");
		}

		$columntype = $get($fieldInstance, 'columntype');
		if ($columntype instanceof \yii\db\ColumnSchemaBuilder) {
			$columntype = (string) $columntype;
		}
		if (is_string($columntype) && strpos($columntype, '(') === false) {
			$columntype = $columntype ?: 'string(100)';
		}

		$moduleName = $moduleModel->getName();
		$lcaseModule = strtolower($moduleName);
		$defaultBaseTable = null;
		if (isset($this->module) && $this->module instanceof \vtlib\Module && !empty($this->module->basetable)) {
			$defaultBaseTable = (string) $this->module->basetable;
		} else {
			$defaultBaseTable = $moduleModel->getBasetable()
				?: \App\ModuleManagement\Services\ModuleService::entityTableName($moduleName);
		}
		$defaultCustomTable = null;
		if (isset($this->module) && $this->module instanceof \vtlib\Module && !empty($this->module->customtable)) {
			$defaultCustomTable = (string) $this->module->customtable;
		} else {
			$defaultCustomTable = $moduleModel->getCustomtable() ?: $defaultBaseTable . 'cf';
		}

		$table = $get($fieldInstance, 'table', $defaultBaseTable);
		if (!$table && isset($this->label) && $this->label === 'LBL_CUSTOM_INFORMATION') {
			$table = $defaultCustomTable;
		}
		if (!$table) {
			$table = $defaultBaseTable;
		}

		$column = $get($fieldInstance, 'column') ?: $get($fieldInstance, 'name');

		$def = \App\Field\FieldDefinition::fromRow([
			'fieldid'             => 0,
			'tabid'               => $moduleId,
			'fieldname'           => (string) $get($fieldInstance, 'name', ''),
			'fieldlabel'          => (string) ($get($fieldInstance, 'label') ?: $get($fieldInstance, 'name', '')),
			'tablename'           => $table,
			'columnname'          => $column,
			'columntype'          => $columntype,
			'uitype'              => (int) $get($fieldInstance, 'uitype', 1),
			'typeofdata'          => (string) $get($fieldInstance, 'typeofdata', 'V'),
			'displaytype'         => (int) $get($fieldInstance, 'displaytype', 1),
			'generatedtype'       => (int) $get($fieldInstance, 'generatedtype', 1),
			'readonly'            => (bool) $get($fieldInstance, 'readonly', false),
			'mandatory'           => isset($fieldInstance->mandatory) ? (bool) $fieldInstance->mandatory : false,
			'presence'            => (int) $get($fieldInstance, 'presence', 0),
			'defaultvalue'        => (string) $get($fieldInstance, 'defaultvalue', ''),
			'maximumlength'       => (int) $get($fieldInstance, 'maximumlength', 100),
			'sequence'            => (int) ($get($fieldInstance, 'sequence') ?? 0),
			'block'               => $blockId,
			'masseditable'        => (int) $get($fieldInstance, 'masseditable', 1),
			'quickcreate'         => (int) $get($fieldInstance, 'quickcreate', 1),
			'quickcreatesequence' => ($qs = $get($fieldInstance, 'quicksequence')) !== null ? (int) $qs : null,
			'info_type'           => (string) $get($fieldInstance, 'info_type', 'BAS'),
			'fieldparams'         => (string) $get($fieldInstance, 'fieldparams', ''),
			'helpinfo'            => (string) $get($fieldInstance, 'helpinfo', ''),
			'summaryfield'        => (int) $get($fieldInstance, 'summaryfield', 0),
			'header_field'        => $get($fieldInstance, 'header_field'),
			'maxlengthtext'       => (int) $get($fieldInstance, 'maxlengthtext', 0),
			'maxwidthcolumn'      => (int) $get($fieldInstance, 'maxwidthcolumn', 0),
		]);

		$newFieldModel = \App\Modules\Base\Models\Field::create($moduleId, $blockId, $def);
		$fieldId = $newFieldModel->getId();
		$fieldInstance->id     = $fieldId;
		$fieldInstance->tabid  = $moduleId;
		$fieldInstance->block  = $this;
		if (method_exists($fieldInstance, 'set')) {
			$fieldInstance->set('id', $fieldId);
		}
		// Attach the hydrated model so callers can invoke setPicklistValues/setRelatedModules
		$fieldInstance->_model = $newFieldModel;

		\App\Utils\VTCacheUtils::updateFieldInfo(
			$moduleId,
			$get($fieldInstance, 'name'),
			$fieldId,
			$get($fieldInstance, 'label'),
			$column,
			$table,
			(int) $get($fieldInstance, 'uitype', 1),
			$get($fieldInstance, 'typeofdata', 'V~O'),
			(int) $get($fieldInstance, 'presence', 0)
		);

		return $this;
	}

	/**
	 * Helper function to log messages
	 * @param string Message to log
	 * @param bool true appends linebreak, false to avoid it
	 */
	public static function log($message, $delim = true)
	{
		\vtlib\Utils::Log($message, $delim);
	}

	/**
	 * Get instance of block
	 * @param int|string block id or block label
	 * @param Module Module Instance of the module if block label is passed
	 * @return self|false
	 */
	public static function getInstance($value, $moduleInstance = false)
	{
		$blockService = ServiceLocator::getBlockService();
		$block = $blockService->getInstance($value, $moduleInstance ? \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInstance->id) : null);
		
		if (!$block) {
			return false;
		}
		
		$instance = new self();
		$instance->id = $block->getId();
		$instance->label = $block->getLabel();
		$instance->sequence = $block->getSequence();
		$instance->showtitle = $block->getShowtitle();
		$instance->visible = $block->getVisible();
		$instance->increateview = $block->getIncreateview();
		$instance->ineditview = $block->getIneditview();
		$instance->indetailview = $block->getIndetailview();
		$instance->display_status = $block->getDisplay_status();
		$instance->iscustom = $block->getIscustom();
		$instance->module = $moduleInstance;
		
		return $instance;
	}

	/**
	 * Get all block instances associated with the module
	 * @param Module Module Instance of the module
	 * @return array
	 */
	public static function getAllForModule($moduleInstance)
	{
		$blockService = ServiceLocator::getBlockService();
		$moduleModel = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInstance->id);
		if (!$moduleModel) {
			return [];
		}
		$blocks = $blockService->getAllForModule($moduleModel->getId());
		
		$instances = [];
		foreach ($blocks as $block) {
			$instance = new self();
			$instance->id = $block->getId();
			$instance->label = $block->getLabel();
			$instance->sequence = $block->getSequence();
			$instance->showtitle = $block->getShowtitle();
			$instance->visible = $block->getVisible();
			$instance->increateview = $block->getIncreateview();
			$instance->ineditview = $block->getIneditview();
			$instance->indetailview = $block->getIndetailview();
			$instance->display_status = $block->getDisplay_status();
			$instance->iscustom = $block->getIscustom();
			$instance->module = $moduleInstance;
			$instances[] = $instance;
		}
		
		return $instances;
	}

	/**
	 * Delete all blocks associated with module
	 * @param Module Module Instance of module to use
	 * @param bool true to delete associated fields, false otherwise
	 */
	public static function deleteForModule($moduleInstance, $recursive = true)
	{
		$blockService = ServiceLocator::getBlockService();
		$moduleModel = \App\ModuleManagement\ServiceLocator::getModuleService()->getInstance($moduleInstance->id);
		$blockService->deleteForModule($moduleModel->getId(), $recursive);
		self::log("Deleting blocks for module ... DONE");
	}
}

