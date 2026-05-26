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

/**
 * Filter adapter class.
 *
 * Backward compatibility adapter for vtlib\Filter.
 * Handles custom view creation using ModuleManagement services.
 */
class Filter
{
	/** @var int|null ID of this filter instance */
	public $id;
	/** @var string|null Filter name */
	public $name;
	/** @var int|bool Default flag */
	public $isdefault = 0;
	/** @var string|false Status value */
	public $status = false;
	/** @var int|bool Metrics flag */
	public $inmetrics = 0;
	/** @var string|null Entity type */
	public $entitytype = null;
	/** @var int Presence */
	public $presence = 1;
	/** @var int Featured flag */
	public $featured = 0;
	/** @var string|null Description */
	public $description = null;
	/** @var int Privileges flag */
	public $privileges = 1;
	/** @var string|null Sort definition */
	public $sort = null;
	/** @var int|null Sequence */
	public $sequence = null;
	/** @var Module|null Owning module instance */
	public $module;

	/**
	 * Create filter record.
	 *
	 * @param Module $moduleInstance
	 * @return void
	 */
	protected function __create(Module $moduleInstance): void
	{
		$this->module = $moduleInstance;
		$this->isdefault = ($this->isdefault === true || $this->isdefault === 'true' || (int) $this->isdefault === 1) ? 1 : 0;
		$this->inmetrics = ($this->inmetrics === true || $this->inmetrics === 'true' || (int) $this->inmetrics === 1) ? 1 : 0;

		if (!isset($this->sequence)) {
			$sequence = (new \App\Db\Query())
				->from('vtiger_customview')
				->where(['entitytype' => $moduleInstance->name])
				->max('sequence');
			$this->sequence = $sequence ? ((int) $sequence + 1) : 0;
		}
		if (!isset($this->status)) {
			$this->status = $this->presence == 0 ? '0' : '3';
		}

		$db = \App\Db\Db::getInstance();
		$db->createCommand()->insert('vtiger_customview', [
			'viewname' => $this->name,
			'setdefault' => $this->isdefault,
			'setmetrics' => $this->inmetrics,
			'entitytype' => $moduleInstance->name,
			'status' => $this->status,
			'privileges' => $this->privileges,
			'featured' => $this->featured,
			'sequence' => $this->sequence,
			'presence' => $this->presence,
			'description' => $this->description,
			'sort' => $this->sort,
		])->execute();
		$this->id = (int) $db->getLastInsertID('vtiger_customview_cvid_seq');
		self::log("Creating Filter {$this->name} ... DONE");
	}

	/**
	 * Save filter definition.
	 *
	 * @param Module $moduleInstance
	 * @return int|bool
	 */
	public function save($moduleInstance = false)
	{
		if (!$moduleInstance instanceof Module) {
			throw new \InvalidArgumentException('Module instance is required to save filter');
		}
		if ($this->id) {
			self::log("Updating Filter {$this->name} ... DONE");
		} else {
			$this->__create($moduleInstance);
		}
		return $this->id;
	}

	/**
	 * Delete filter.
	 *
	 * @return void
	 */
	public function delete(): void
	{
		if (!$this->id) {
			return;
		}
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->delete('vtiger_cvadvfilter', ['cvid' => $this->id])->execute();
		$db->createCommand()->delete('vtiger_cvcolumnlist', ['cvid' => $this->id])->execute();
		$db->createCommand()->delete('vtiger_customview', ['cvid' => $this->id])->execute();
		self::log("Deleting Filter {$this->name} ... DONE");
	}

	/**
	 * Get column value string for custom view tables.
	 *
	 * @param Field $fieldInstance
	 * @return string
	 */
	protected function getColumnValue($fieldInstance): string
	{
		$typeofdata = isset($fieldInstance->typeofdata) ? $fieldInstance->typeofdata : 'V';
		$moduleName = method_exists($fieldInstance, 'getModuleName') ? $fieldInstance->getModuleName() : ($this->module->name ?? '');
		$label = isset($fieldInstance->label) ? $fieldInstance->label : '';
		$table = isset($fieldInstance->table) ? $fieldInstance->table : '';
		$column = isset($fieldInstance->column) ? $fieldInstance->column : '';
		$name = isset($fieldInstance->name) ? $fieldInstance->name : '';
		$displayinfo = $moduleName . '_' . str_replace(' ', '_', $label) . ':' . $typeofdata;
		return "{$table}:{$column}:{$name}:{$displayinfo}";
	}

	/**
	 * Add field to filter definition.
	 *
	 * @param Field $fieldInstance
	 * @param int $index
	 * @return $this
	 */
	public function addField($fieldInstance, int $index = 0)
	{
		if (!$this->id) {
			throw new \RuntimeException('Filter must be saved before adding fields');
		}
		$cvcolvalue = $this->getColumnValue($fieldInstance);
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->update(
			'vtiger_cvcolumnlist',
			['columnindex' => new \yii\db\Expression('columnindex + 1')],
			['and', ['cvid' => $this->id], ['>=', 'columnindex', $index]]
		)->execute();
		$db->createCommand()->insert('vtiger_cvcolumnlist', [
			'cvid' => $this->id,
			'columnindex' => $index,
			'columnname' => $cvcolvalue
		])->execute();
		self::log("Adding {$fieldInstance->name} to {$this->name} filter ... DONE");
		return $this;
	}

	/**
	 * Add advanced filter rule.
	 *
	 * @param Field $fieldInstance
	 * @param string $comparator
	 * @param string $comparevalue
	 * @param int $index
	 * @param int $group
	 * @param string $condition
	 * @return $this
	 */
	public function addRule($fieldInstance, $comparator, $comparevalue, int $index = 0, int $group = 1, string $condition = 'and')
	{
		if (!$this->id || empty($comparator)) {
			return $this;
		}
		$cvcolvalue = $this->getColumnValue($fieldInstance);
		$comp = self::translateComparator($comparator);
		$db = \App\Db\Db::getInstance();
		$db->createCommand()->update(
			'vtiger_cvadvfilter',
			['columnindex' => new \yii\db\Expression('columnindex + 1')],
			['and', ['cvid' => $this->id], ['>=', 'columnindex', $index]]
		)->execute();
		$db->createCommand()->insert('vtiger_cvadvfilter', [
			'cvid' => $this->id,
			'columnindex' => $index,
			'columnname' => $cvcolvalue,
			'comparator' => $comp,
			'value' => $comparevalue,
			'groupid' => $group,
			'column_condition' => $condition
		])->execute();
		return $this;
	}

	/**
	 * Translate comparator to long/short form.
	 *
	 * @param string $value
	 * @param bool $tolongform
	 * @return string
	 */
	public static function translateComparator($value, $tolongform = false)
	{
		$map = [
			'EQUALS' => 'e',
			'NOT_EQUALS' => 'n',
			'STARTS_WITH' => 's',
			'ENDS_WITH' => 'ew',
			'CONTAINS' => 'c',
			'DOES_NOT_CONTAINS' => 'k',
			'LESS_THAN' => 'l',
			'GREATER_THAN' => 'g',
			'LESS_OR_EQUAL' => 'm',
			'GREATER_OR_EQUAL' => 'h',
		];
		if ($tolongform) {
			$flip = array_flip($map);
			$key = strtolower($value);
			return $flip[$key] ?? strtoupper($value);
		}
		$upper = strtoupper($value);
		return $map[$upper] ?? strtolower($value);
	}

	/**
	 * Helper logger.
	 *
	 * @param string $message
	 * @param bool $delim
	 * @return void
	 */
	public static function log($message, $delim = true): void
	{
		\vtlib\Utils::Log($message, $delim);
	}
}

