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

/**
 * Vtiger Field Model Class
 */

use App\Field\FieldDefinition;
use App\Webservices\WebserviceField;


class Field
{

	/** Immutable DB-mirror — replaced via with(), never mutated in place. */
	protected ?FieldDefinition $definition = null;

	/** Hydrated Block instance — lazily resolved from $definition->block. */
	protected mixed $blockInstance = null;

	public $webserviceField = false;
	public static $referenceTypes = ['reference', 'referenceLink', 'referenceProcess', 'referenceSubProcess'];

	protected $fieldType;
	protected $fieldDataTypeShort;
	protected $uitype_instance;

	// Runtime state — not persisted in vtiger_field
	protected $fieldDataType;
	protected $module;
	protected $isReadOnly;
	protected $fieldInfo;
	protected $uitypeModel;
	protected $fieldvalue;
	protected $workflow_columnname;
	protected $isEditableReadOnly;
	protected $editable;
	protected $reportcolumn;
	protected $reportlabel;
	protected $reportcolumninfo;
	protected $fromOutsideList;
	protected $uitype;
	protected $name;
	protected $label;
	protected $typeofdata;
	protected $diplaytype;
	protected $type;
	protected $permissions;
	protected $sort;

	/** @var array<string, mixed> vtiger_field row keys before FieldDefinition is hydrated */
	private array $pendingDefinitionRow = [];

	/** Legacy / alias setter names → FieldDefinition property names */
	private const DEFINITION_PROPERTY_ALIASES = [
		'fieldid' => 'id',
		'fieldname' => 'name',
		'fieldlabel' => 'label',
		'tablename' => 'table',
		'columnname' => 'column',
		'quicksequence' => 'quickcreatesequence',
	];

	/** FieldDefinition property names → vtiger_field row keys for fromRow() */
	private const DEFINITION_TO_ROW_KEY = [
		'id' => 'fieldid',
		'name' => 'fieldname',
		'label' => 'fieldlabel',
		'table' => 'tablename',
		'column' => 'columnname',
	];

	const REFERENCE_TYPE = 'reference';
	const OWNER_TYPE = 'owner';
	const CURRENCY_LIST = 'currencyList';
	const QUICKCREATE_MANDATORY = 0;
	const QUICKCREATE_NOT_ENABLED = 1;
	const QUICKCREATE_ENABLED = 2;
	const QUICKCREATE_NOT_PERMITTED = 3;

	public function __construct(?FieldDefinition $definition = null)
	{
		$this->definition = $definition;
	}

	/** Preferred factory — hydrates from a vtiger_field DB row (or superset). */
	public static function fromRow(array $row): static
	{
		return new static(FieldDefinition::fromRow($row));
	}

	public function getDefinition(): ?FieldDefinition
	{
		return $this->definition;
	}

	/**
	 * Property reader that proxies DB-mirror properties through FieldDefinition
	 * and falls back to declared runtime-state properties.
	 *
	 * 'quicksequence' is a backward-compat alias for FieldDefinition::$quickcreatesequence
	 * (vtlib\FieldBasic used the old name).
	 * 'readonly' and 'mandatory' are cast to int for backward compat (legacy code uses === 0/1).
	 */
	public function get($propertyName)
	{
		if ($propertyName === 'block') {
			if ($this->blockInstance !== null) {
				return $this->blockInstance;
			}
			$blockId = $this->definition?->block;
			if ($blockId === null) {
				return null;
			}
			// Lazy-hydrate block from vtiger_blocks so templates can access ->id and ->label
			$row = \App\Cache\Cache::get('BlockRow', $blockId);
			if ($row === false) {
				$row = (new \App\Db\Query())
					->from('vtiger_blocks')
					->where(['blockid' => $blockId])
					->one();
				\App\Cache\Cache::save('BlockRow', $blockId, $row ?: []);
			}
			if ($row) {
				$stub = new \stdClass();
				$stub->id       = (int) $row['blockid'];
				$stub->label    = $row['blocklabel'] ?? '';
				$stub->sequence = (int) ($row['sequence'] ?? 0);
				$this->blockInstance = $stub;
				return $stub;
			}
			return $blockId;
		}
		if ($propertyName === 'quicksequence') {
			return $this->definition?->quickcreatesequence;
		}
		if ($propertyName === 'readonly' && $this->definition !== null) {
			return (int) $this->definition->readonly;
		}
		if ($propertyName === 'mandatory' && $this->definition !== null) {
			return (int) $this->definition->mandatory;
		}
		if ($this->definition !== null && property_exists($this->definition, $propertyName)) {
			return $this->definition->$propertyName;
		}
		if (property_exists($this, $propertyName)) {
			return $this->$propertyName;
		}
		return null;
	}

	/**
	 * Property writer that proxies DB-mirror properties through FieldDefinition::with()
	 * and writes runtime-state properties directly.
	 */
	public function set($name, $value)
	{
		if ($name === 'block') {
			if (is_object($value)) {
				$this->blockInstance = $value;
			} else {
				$this->blockInstance = null;
				$intVal = ($value !== null && $value !== false) ? (int) $value : null;
				$this->assignDefinitionProperty('block', $intVal);
			}
			return $this;
		}
		if ($name === 'quicksequence') {
			$intVal = ($value !== null && $value !== false) ? (int) $value : null;
			$this->assignDefinitionProperty('quickcreatesequence', $intVal);
			return $this;
		}
		$definitionProperty = self::resolveDefinitionProperty($name);
		if ($definitionProperty !== null) {
			$this->assignDefinitionProperty($definitionProperty, $value);
			return $this;
		}
		if (property_exists($this, $name)) {
			$this->$name = $value;
			return $this;
		}
		return $this;
	}

	private static function resolveDefinitionProperty(string $name): ?string
	{
		if (array_key_exists($name, self::DEFINITION_PROPERTY_ALIASES)) {
			return self::DEFINITION_PROPERTY_ALIASES[$name];
		}
		return property_exists(FieldDefinition::class, $name) ? $name : null;
	}

	private static function definitionPropertyToRowKey(string $property): string
	{
		return self::DEFINITION_TO_ROW_KEY[$property] ?? $property;
	}

	private function assignDefinitionProperty(string $property, mixed $value): void
	{
		if ($this->definition !== null) {
			$this->definition = $this->definition->with([$property => $value]);
			return;
		}

		$this->pendingDefinitionRow[self::definitionPropertyToRowKey($property)] = $value;
		$this->definition = FieldDefinition::fromRow(array_merge([
			'fieldid' => 0,
			'tabid' => 0,
			'fieldname' => '',
			'fieldlabel' => '',
			'tablename' => '',
			'columnname' => '',
			'uitype' => 1,
		], $this->pendingDefinitionRow));
	}

	/**
	 * Function to get the Field Id
	 * @return int
	 */
	public function getId()
	{
		return $this->definition?->id;
	}

	/**
	 * Get name
	 * @return string
	 */
	public function getName()
	{
		return $this->definition?->name;
	}

	/**
	 * Get field name
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->definition?->name;
	}

	/**
	 * Get field label
	 * @return string
	 */
	public function getLabel(): string
	{
		return $this->definition?->label ?? '';
	}

	/**
	 * Get field label
	 * @return string
	 */
	public function getFieldLabel()
	{
		return $this->definition?->label;
	}

	/**
	 * Get table name
	 * @return string
	 */
	public function getTableName()
	{
		return $this->definition?->table;
	}

	/**
	 * Get column label
	 * @return string
	 */
	public function getColumnName()
	{
		return $this->definition?->column;
	}

	/**
	 * Get ui type
	 * @return int
	 */
	public function getUIType()
	{
		return $this->definition?->uitype;
	}

	/**
	 * Returns the module name owning this field, or null if tabid is not set.
	 */
	public function getModuleName(): ?string
	{
		$tabid = $this->definition?->tabid;
		if (!$tabid) {
			return null;
		}
		$moduleName = \App\Utils\ModuleUtils::getModuleName($tabid);
		return $moduleName !== false ? $moduleName : null;
	}

	/**
	 * Function to retrieve full data
	 * @return <array>
	 */
	public function getData()
	{
		return get_object_vars($this);
	}

	public function getModule()
	{
		if (!isset($this->module) || !$this->module) {
			$moduleObj = null;
			$block = $this->get('block');
			if (is_object($block) && isset($block->module)) {
				$moduleObj = $block->module;
			} elseif (is_numeric($block)) {
				$blockInstance = \App\Modules\Base\Models\Block::getInstance((int) $block);
				if ($blockInstance && isset($blockInstance->module)) {
					$moduleObj = $blockInstance->module;
				}
			}

			if (!$moduleObj && $this->getModuleId()) {
				$moduleObj = \App\Modules\Base\Models\Module::getInstance($this->getModuleId());
			}

			if (empty($moduleObj)) {
				return false;
			}

			if ($moduleObj instanceof \App\Modules\Base\Models\Module) {
				$this->module = $moduleObj;
			} elseif ($moduleObj instanceof \vtlib\Module) {
				$this->module = \App\Modules\Base\Models\Module::getInstanceFromModuleObject($moduleObj);
			} else {
				$moduleName = null;
				if (is_string($moduleObj)) {
					$moduleName = $moduleObj;
				} elseif (is_numeric($moduleObj)) {
					$moduleName = \App\Utils\ModuleUtils::getModuleName((int) $moduleObj);
				}

				if ($moduleName) {
					$this->module = \App\Modules\Base\Models\Module::getInstance($moduleName);
				}
			}
		}
		return $this->module;
	}

	public function setModule($moduleInstance)
	{
		$this->module = $moduleInstance;
	}

	public function getBlockId()
	{
		$block = $this->get('block');
		if (is_object($block)) {
			if (method_exists($block, 'getId')) {
				return $block->getId();
			}
			// vtlib\Block and other block objects often expose id as a property
			if (isset($block->id)) {
				return $block->id;
			}
			if (isset($block->blockid)) {
				return $block->blockid;
			}
			// Generic getter patterns
			if (method_exists($block, 'get')) {
				$maybeId = $block->get('id') ?? $block->get('blockid') ?? null;
				if ($maybeId !== null) {
					return $maybeId;
				}
			}
		}
		return $block;
	}

	/**
	 * Get block name (label)
	 * @return string
	 */
	public function getBlockName()
	{
		$block = $this->get('block');
		if (is_object($block)) {
			if (method_exists($block, 'get')) {
				return $block->get('label');
			}
			return $block->label ?? '';
		}
		// If block is just an ID, fetch the block name from database
		if (is_numeric($block)) {
			return \App\Utils\Utils::getBlockName($block);
		}
		return '';
	}

	public function getBlockSequence(): int
	{
		$block = $this->get('block');
		if (is_object($block)) {
			return (int) ($block->sequence ?? 0);
		}

		return (int) ($block ?? 0);
	}

	public function initialize($valuemap)
	{
		// Handle block: extract the ID if it is a hydrated object so fromRow() gets a ?int
		if (isset($valuemap['block']) && is_object($valuemap['block'])) {
			$blockObj = $valuemap['block'];
			$this->blockInstance = $blockObj;
			$valuemap['block'] = $blockObj->id ?? ($blockObj->blockid ?? null);
		}
		// Legacy callers may pass 'quicksequence' (vtlib\FieldBasic property name)
		// rather than the DB column name 'quickcreatesequence'
		if (array_key_exists('quicksequence', $valuemap) && !array_key_exists('quickcreatesequence', $valuemap)) {
			$valuemap['quickcreatesequence'] = $valuemap['quicksequence'];
		}
		$this->definition = FieldDefinition::fromRow($valuemap);
		return $this;
	}

	public function getModuleId()
	{
		return $this->definition?->tabid;
	}

	/**
	 * Function to retieve display value for a value
	 * @param string $value - value which need to be converted to display value
	 * @return string - converted display value
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return $this->getUITypeModel()->getDisplayValue($value, $record, $recordInstance, $rawText);
	}

	/**
	 * Function to retrieve display type of a field
	 * @return string - display type of the field
	 */
	public function getDisplayType()
	{
		return $this->get('displaytype');
	}

	/**
	 * Function to get the Webservice Field Object for the current Field Object
	 * @return WebserviceField instance
	 */
	public function getWebserviceFieldObject()
	{
		if ($this->webserviceField === false) {
			$db = \App\Database\PearDatabase::getInstance();

			$row = [];
			$row['uitype'] = $this->get('uitype');
			$row['block'] = $this->get('block');
			$row['tablename'] = $this->get('table');
			$row['columnname'] = $this->get('column');
			$row['fieldname'] = $this->get('name');
			$row['fieldlabel'] = $this->get('label');
			$row['displaytype'] = $this->get('displaytype');
			$row['masseditable'] = $this->get('masseditable');
			$row['typeofdata'] = $this->get('typeofdata');
			$row['presence'] = $this->get('presence');
			$row['tabid'] = $this->getModuleId();
			$row['fieldid'] = $this->get('id');
			$row['readonly'] = !$this->getProfileReadWritePermission();
			$row['defaultvalue'] = $this->get('defaultvalue');
			$row['fieldparams'] = $this->get('fieldparams');
			$this->webserviceField = \App\Webservices\WebserviceField::fromArray($db, $row);
		}
		return $this->webserviceField;
	}

	/**
	 * Maps uitype integer to semantic type string used by business logic:
	 * import, workflows, reports, query conditions, and field validation.
	 * uitypes absent from this map fall back to vtiger_ws_fieldtype (via WebserviceField).
	 */
	private const SEMANTIC_TYPE_MAP = [
		3   => 'autogenerated',
		4   => 'recordNumber',
		8   => 'totalTime',
		9   => 'percentage',
		10  => 'reference',
		11  => 'phone',
		13  => 'email',
		15  => 'picklist',
		16  => 'multipicklist',
		17  => 'url',
		26  => 'documentsFolder',
		27  => 'fileLocationType',
		28  => 'documentsFileUpload',
		32  => 'languages',
		33  => 'picklist',
		50  => 'reference',
		51  => 'reference',
		52  => 'userCreator',
		53  => 'owner',
		54  => 'multiowner',
		56  => 'boolean',
		57  => 'reference',
		58  => 'reference',
		59  => 'reference',
		61  => 'file',
		66  => 'referenceProcess',
		67  => 'referenceLink',
		68  => 'referenceSubProcess',
		69  => 'image',
		71  => 'currency',
		72  => 'currency',
		73  => 'reference',
		75  => 'reference',
		76  => 'reference',
		80  => 'reference',
		81  => 'reference',
		98  => 'userRole',
		101 => 'reference',
		117 => 'currencyList',
		120 => 'sharedOwner',
		156 => 'boolean',
		301 => 'modules',
		302 => 'tree',
		303 => 'taxes',
		304 => 'inventoryLimit',
		305 => 'multiReferenceValue',
		308 => 'rangeTime',
		309 => 'categoryMultipicklist',
		311 => 'multiImage',
		342 => 'recurrence',
		357 => 'reference',
		358 => 'mailSmtpSelect',
	];

	/**
	 * Returns the logical (semantic) type of this field.
	 * Used by import, workflows, reports, query conditions, and the UiType renderer.
	 * Examples: 'picklist', 'owner', 'reference', 'datetime', 'string'.
	 */
	public function getFieldDataType()
	{
		return $this->getUiTypeName();
	}

	/**
	 * Resolves the logical field type from the uitype integer.
	 * uitype 55 is disambiguated by field name (salutationtype → picklist, firstname → salutation).
	 * All other unmapped uitypes delegate to vtiger_ws_fieldtype via WebserviceField.
	 */
	public function getUiTypeName(): string
	{
		if (!isset($this->fieldDataType)) {
			$uitype = (int) $this->get('uitype');
			if ($uitype === 55) {
				if ($this->getName() === 'salutationtype') {
					$this->fieldDataType = 'picklist';
				} elseif ($this->getName() === 'firstname') {
					$this->fieldDataType = 'salutation';
				} else {
					$this->fieldDataType = $this->getWebserviceFieldObject()->getFieldDataType();
				}
			} else {
				$this->fieldDataType = self::SEMANTIC_TYPE_MAP[$uitype]
					?? $this->getWebserviceFieldObject()->getFieldDataType();
			}
		}
		return $this->fieldDataType;
	}

	/**
	 * Function to get list of modules the field refernced to
	 * @return string[] list of modules for which field is refered to
	 */
	public function getReferenceList()
	{
		if (\App\Cache\Cache::has('getReferenceList', $this->getId())) {
			return \App\Cache\Cache::get('getReferenceList', $this->getId());
		}
		if (method_exists($this->getUITypeModel(), 'getReferenceList')) {
			$list = $this->getUITypeModel()->getReferenceList();
		} else {
			if ($this->getUIType() === 10) {
				$query = (new \App\Db\Query())->select(['module' => 'relmodule'])
					->from('vtiger_fieldmodulerel')
					->innerJoin('vtiger_tab', 'vtiger_tab.name = vtiger_fieldmodulerel.relmodule')
					->where(['fieldid' => $this->getId()])
					->andWhere(['<>', 'vtiger_tab.presence', 1])
					->orderBy(['sequence' => SORT_ASC]);
			} else {
				$query = (new \App\Db\Query())->select(['module' => 'vtiger_ws_referencetype.type'])
					->from('vtiger_ws_referencetype')
					->innerJoin('vtiger_ws_fieldtype', 'vtiger_ws_referencetype.fieldtypeid = vtiger_ws_fieldtype.fieldtypeid')
					->innerJoin('vtiger_tab', 'vtiger_tab.name = vtiger_ws_referencetype.type')
					->where(['vtiger_ws_fieldtype.uitype' => $this->getUIType()])
					->andWhere(['<>', 'vtiger_tab.presence', 1]);
			}
			$list = [];
			foreach ($query->column() as $moduleName) {
				if (\App\Security\Privilege::isPermitted($moduleName)) {
					$list[] = $moduleName;
				}
			}
		}
		\App\Cache\Cache::save('getReferenceList', $this->getId(), $list);
		return $list;
	}

	/**
	 * Function to check if the field is named field of the module
	 * @return boolean - True/False
	 */
	public function isNameField()
	{
		$moduleModel = $this->getModule();
		if (!$moduleModel) {
			return false;
		}
		$nameFields = $moduleModel->getNameFields();
		if (!is_array($nameFields)) {
			return false;
		}
		if (in_array($this->get('column'), $nameFields)) {
			return true;
		}
		return false;
	}

	/**
	 * Function to check whether the current field is read-only
	 * @return boolean - true/false
	 */
	public function isReadOnly()
	{
		if (isset($this->isReadOnly)) {
			return $this->isReadOnly;
		}
		return $this->isReadOnly = !$this->getProfileReadWritePermission();
	}

	/**
	 * Function to get the UI Type model for the uitype of the current field
	 * @return \App\Modules\Base\UiTypes\BaseUiType or UI Type specific model instance
	 */
	public function getUITypeModel()
	{
		if (!$this->get('uitypeModel')) {
			$this->set('uitypeModel', \App\Modules\Base\UiTypes\BaseUiType::getInstanceFromField($this));
		}
		return $this->get('uitypeModel');
	}

	public function isRoleBased()
	{
		if ($this->get('uitype') === 15 || $this->get('uitype') === 33 || ($this->get('uitype') === 55 && $this->getFieldName() === 'salutationtype')) {
			return true;
		}
		return false;
	}

	/**
	 * Function to get all the available picklist values for the current field
	 * @param boolean $skipCheckingRole
	 * @return <Array> List of picklist values if the field is of type picklist or multipicklist, null otherwise.
	 */
	public function getPicklistValues($skipCheckingRole = false)
	{
		$fieldDataType = $this->getFieldDataType();
		if ($this->getName() == 'hdnTaxType')
			return null;

		if ($fieldDataType == 'picklist' || $fieldDataType == 'multipicklist') {
			if ($this->isRoleBased() && !$skipCheckingRole) {
				$userModel = \App\User\CurrentUser::get();
				$picklistValues = \App\Fields\Picklist::getRoleBasedPicklistValues($this->getName(), $userModel->get('roleid'));
			} else {
				$picklistValues = \App\Fields\Picklist::getPickListValues($this->getName());
			}

			// Protection against deleting a value that does not exist on the list
			if ($fieldDataType == 'picklist') {
				$fieldValue = $this->get('fieldvalue');
				if (!empty($fieldValue) && !in_array($this->get('fieldvalue'), $picklistValues)) {
					$picklistValues[] = $this->get('fieldvalue');
					$this->set('isEditableReadOnly', true);
				}
			}

			$fieldPickListValues = [];
			foreach ($picklistValues as $value) {
				$fieldPickListValues[$value] = \App\Runtime\Vtiger_Language_Handler::translate($value, $this->getModuleName());
			}
			return $fieldPickListValues;
		} else if (method_exists($this->getUITypeModel(), 'getPicklistValues')) {
			return $this->getUITypeModel()->getPicklistValues();
		}
		return null;
	}

	/**
	 * Function to get all the available picklist values for the current field
	 * @return <Array> List of picklist values if the field is of type picklist or multipicklist, null otherwise.
	 */
	public function getModulesListValues()
	{
		$allModules = \vtlib\Functions::getAllModules(true, false, 0);
		$modules = [];
		foreach ($allModules as $module) {
			$modules[$module['tabid']] = [
				'name' => $module['name'],
				'label' => \App\Runtime\Vtiger_Language_Handler::translate($module['name'], $module['name'])
			];
		}
		return $modules;
	}

	public static function showDisplayTypeList()
	{
		$displayType = array(
			1 => 'LBL_DISPLAY_TYPE_1',
			2 => 'LBL_DISPLAY_TYPE_2',
			3 => 'LBL_DISPLAY_TYPE_3',
			4 => 'LBL_DISPLAY_TYPE_4',
			//5 => 'LBL_DISPLAY_TYPE_5',
			10 => 'LBL_DISPLAY_TYPE_10'
		);
		return $displayType;
	}

	/**
	 * Function to check if the current field is mandatory or not
	 * @return boolean - true/false
	 */
	public function isMandatory(): bool
	{
		return (bool) $this->get('mandatory');
	}

	/**
	 * Function to get the field type
	 * @return string type of the field
	 */
	public function getFieldType()
	{
		if (isset($this->fieldType)) {
			return $this->fieldType;
		}
		$fieldType = (string) $this->get('typeofdata');
		if ($this->getFieldDataType() === 'reference') {
			$fieldType = 'V';
		} else {
			$fieldType = \vtlib\Functions::transformFieldTypeOfData($this->get('table'), $this->get('column'), $fieldType);
		}
		return $this->fieldType = $fieldType;
	}

	/**
	 * Function to check if the field is shown in detail view
	 * @return boolean
	 */
	public function isViewEnabled()
	{
		if ($this->getDisplayType() === 4 || in_array($this->get('presence'), [1, 3])) {
			return false;
		}
		return $this->getPermissions();
	}

	/**
	 * Function to check if the field is shown in detail view
	 * @return boolean
	 */
	public function isViewable()
	{
		if (!$this->isViewEnabled() || !$this->isActiveReference() || (($this->get('uitype') === 306 || $this->get('uitype') === 307 || $this->get('uitype') === 311 || $this->get('uitype') === 312) && $this->getDisplayType() === 2)) {
			return false;
		}
		return true;
	}

	/**
	 * Function to check if the field is shown in detail view
	 * @return boolean
	 */
	public function isViewableInDetailView()
	{
		if (!$this->isViewable() || $this->getDisplayType() === 3 || $this->getDisplayType() === 5) {
			return false;
		}
		return true;
	}

	/**
	 * Function to check whether the current field is writable 
	 * @return boolean
	 */
	public function isWritable()
	{
		$displayType = $this->get('displaytype');
		if (!$this->isViewEnabled() || $displayType === 4 || $displayType === 5 ||
			strcasecmp($this->getFieldDataType(), 'autogenerated') === 0 ||
			strcasecmp($this->getFieldDataType(), 'id') === 0) {
			return false;
		}
		return true;
	}

	/**
	 * Function to check whether the current field is editable 
	 * @return boolean
	 */
	public function isEditable()
	{
		if ($this->isRelationOnlyField()) {
			return false;
		}
		$displayType = $this->get('displaytype');
		if (!$this->isWritable() || ( $displayType !== 1 && $displayType !== 10 ) || $this->isReadOnly() === true || $this->get('uitype') === 4) {
			return false;
		}
		return true;
	}

	/**
	 * Relation-only fields live in vtiger_field for metadata but must not appear on entity forms.
	 */
	public function isRelationOnlyField(): bool
	{
		$helpinfo = $this->get('helpinfo');
		if ($helpinfo && str_contains((string) $helpinfo, 'relation_only')) {
			return true;
		}
		$fieldparams = (string) $this->get('fieldparams');
		if (str_contains($fieldparams, 'relation_table=')) {
			return true;
		}
		$tablename = (string) $this->get('tablename');
		return str_contains($tablename, '_relations_') || str_contains($tablename, '_relation_');
	}

	/**
	 * Function to check whether field is ajax editable
	 * @return boolean
	 */
	public function isAjaxEditable()
	{
		$ajaxRestrictedFields = array('4', '72', '10', '300', '51', '59');
		if (!$this->isEditable() || in_array($this->get('uitype'), $ajaxRestrictedFields) || !$this->getUITypeModel()->isAjaxEditable() || (int) $this->get('displaytype') === 10) {
			return false;
		}
		return true;
	}

	public function isEditableReadOnly()
	{
		$isEditableReadOnly = $this->get('isEditableReadOnly');

		if ($isEditableReadOnly !== null) {
			return $isEditableReadOnly;
		}
		if ((int) $this->get('displaytype') === 10) {
			return true;
		}
		return false;
	}

	public function isQuickCreateEnabled()
	{
		$moduleModel = $this->getModule();
		$quickCreate = $this->get('quickcreate');
		if (($quickCreate == self::QUICKCREATE_MANDATORY || $quickCreate == self::QUICKCREATE_ENABLED || $this->isMandatory()) && $this->get('uitype') != 69 && $this->get('uitype') != 311) {
			//isQuickCreateSupported will not be there for settings
			if (method_exists($moduleModel, 'isQuickCreateSupported') && $moduleModel->isQuickCreateSupported()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Function to check whether summary field or not
	 * @return boolean true/false
	 */
	public function isSummaryField()
	{
		return ($this->get('summaryfield')) ? true : false;
	}

	/**
	 * Function to check whether the current reference field is active
	 * @return boolean
	 */
	public function isActiveReference()
	{
		if ($this->getFieldDataType() === 'reference' && empty($this->getReferenceList())) {
			return false;
		}
		return true;
	}

	/**
	 * If the field is sortable in ListView
	 */
	public function isListviewSortable()
	{
		return $this->getUITypeModel()->isListviewSortable();
	}

	/**
	 * Hydrate a Field model from a plain data object (previously vtlib\Field).
	 * Maps FieldBasic property names → DB column names expected by FieldDefinition::fromRow().
	 */
	public static function getInstanceFromFieldObject(object $fieldObj)
	{
		$p = get_object_vars($fieldObj);
		$moduleName = $fieldObj->getModuleName();

		if ($moduleName === null && !empty($p['tabid'])) {
			$moduleName = (new \App\Db\Query())
				->select('name')->from('vtiger_tab')
				->where(['tabid' => $p['tabid']])->scalar();
		}
		$moduleName = $moduleName ?? 'Base';

		// Extract block ID from object (hydrated block) or raw value
		$blockRaw = $p['block'] ?? null;
		$blockInstance = null;
		if (is_object($blockRaw)) {
			$blockId = $blockRaw->id ?? ($blockRaw->blockid ?? null);
			$blockInstance = $blockRaw;
		} else {
			$blockId = ($blockRaw !== null && $blockRaw !== false) ? (int) $blockRaw : null;
		}

		$row = [
			'fieldid'             => $p['id'] ?? 0,
			'tabid'               => $p['tabid'] ?? 0,
			'fieldname'           => $p['name'] ?? '',
			'fieldlabel'          => $p['label'] ?? '',
			'tablename'           => $p['table'] ?? '',
			'columnname'          => $p['column'] ?? '',
			'columntype'          => $p['columntype'] ?? null,
			'uitype'              => $p['uitype'] ?? 1,
			'typeofdata'          => $p['typeofdata'] ?? 'V',
			'displaytype'         => $p['displaytype'] ?? 1,
			'generatedtype'       => $p['generatedtype'] ?? 0,
			'readonly'            => $p['readonly'] ?? false,
			'mandatory'           => $p['mandatory'] ?? false,
			'presence'            => $p['presence'] ?? 1,
			'defaultvalue'        => $p['defaultvalue'] ?? '',
			'maximumlength'       => $p['maximumlength'] ?? 100,
			'sequence'            => $p['sequence'] ?? 0,
			'block'               => $blockId,
			'masseditable'        => $p['masseditable'] ?? 1,
			'quickcreate'         => $p['quickcreate'] ?? 1,
			'quickcreatesequence' => $p['quicksequence'] ?? null, // FieldBasic uses old name
			'info_type'           => $p['info_type'] ?? 'BAS',
			'fieldparams'         => $p['fieldparams'] ?? '',
			'helpinfo'            => $p['helpinfo'] ?? '',
			'summaryfield'        => $p['summaryfield'] ?? 0,
			'header_field'        => $p['header_field'] ?? null,
			'maxlengthtext'       => $p['maxlengthtext'] ?? 0,
			'maxwidthcolumn'      => $p['maxwidthcolumn'] ?? 0,
		];

		$className = \App\Core\Loader::getComponentClassName('Model', 'Field', $moduleName);
		/** @var static $fieldModel */
		$fieldModel = $className::fromRow($row);
		$fieldModel->blockInstance = $blockInstance;
		return $fieldModel;
	}

	/**
	 * Function to get the custom view column name transformation of the field for a date field used in date filters
	 * @return string - tablename:columnname:fieldname:module_fieldlabel
	 */
	public function getCVDateFilterColumnName()
	{
		$moduleName = $this->getModuleName();
		$tableName = $this->get('table');
		$columnName = $this->get('column');
		$fieldName = $this->get('name');
		$fieldLabel = $this->get('label');

		$escapedFieldLabel = str_replace(' ', '_', $fieldLabel);
		$moduleFieldLabel = $moduleName . '_' . $escapedFieldLabel;

		return $tableName . ':' . $columnName . ':' . $fieldName . ':' . $moduleFieldLabel;
	}

	/**
	 * Function to get the custom view column name transformation of the field
	 * @return string - tablename:columnname:fieldname:module_fieldlabel:fieldtype
	 */
	public function getCustomViewColumnName()
	{
		$moduleName = $this->getModuleName();
		$tableName = $this->get('table');
		$columnName = $this->get('column');
		$fieldName = $this->get('name');
		$fieldLabel = $this->get('label');
		$fieldType = (string) $this->get('typeofdata');
		//Special condition need for reference field as they should be treated as string field
		if ($this->getFieldDataType() === 'reference') {
			$fieldType = 'V';
		} else {
			$fieldType = \vtlib\Functions::transformFieldTypeOfData($tableName, $columnName, $fieldType);
		}
		$escapedFieldLabel = str_replace(' ', '_', $fieldLabel);
		$moduleFieldLabel = "{$moduleName}_{$escapedFieldLabel}";
		return "$tableName:$columnName:$fieldName:$moduleFieldLabel:$fieldType";
	}

	/**
	 * Function to get the Report column name transformation of the field
	 * @return string - tablename:columnname:module_fieldlabel:fieldname:fieldtype
	 */
	public function getReportFilterColumnName()
	{
		$moduleName = $this->getModuleName();
		$tableName = $this->get('table');
		$columnName = $this->get('column');
		$fieldName = $this->get('name');
		$fieldLabel = $this->get('label');
		$fieldType = (string) $this->get('typeofdata');
		if ($this->getFieldDataType() == 'reference') {
			$fieldType = 'V';
		} else {
			$fieldType = \vtlib\Functions::transformFieldTypeOfData($tableName, $columnName, $fieldType);
		}
		$escapedFieldLabel = str_replace(' ', '_', $fieldLabel);
		$moduleFieldLabel = $moduleName . '_' . $escapedFieldLabel;

		if ($tableName == 'vtiger_crmentity' && $columnName != 'smownerid') {
			$tableName = 'vtiger_crmentity' . $moduleName;
		} elseif ($columnName == 'smownerid') {
			$tableName = 'vtiger_users' . $moduleName;
			$columnName = 'user_name';
		}

		return $tableName . ':' . $columnName . ':' . $moduleFieldLabel . ':' . $fieldName . ':' . $fieldType;
	}

	/**
	 * This is set from Workflow Record Structure, since workflow expects the field name
	 * in a different format in its filter. Eg: for module field its fieldname and for reference
	 * fields its reference_field_name : (reference_module_name) field - salesorder_id: (SalesOrder) subject
	 * @return string
	 */
	public function getWorkFlowFilterColumnName()
	{
		return $this->get('workflow_columnname');
	}

	/**
	 * Function to get the field details
	 * @param array $context Optional context information (module, mode, etc.)
	 * @return <Array> - array of field values
	 */
	public function getFieldInfo($context = [])
	{
		$currentUser = \App\User\CurrentUser::get();
		$fieldDataType = $this->getFieldDataType();

		$this->fieldInfo['mandatory'] = $this->isMandatory();
		$this->fieldInfo['presence'] = $this->isActiveField();
		$this->fieldInfo['quickcreate'] = $this->isQuickCreateEnabled();
		$this->fieldInfo['masseditable'] = $this->isMassEditable();
		$this->fieldInfo['header_field'] = $this->isHeaderField();
		$this->fieldInfo['maxlengthtext'] = $this->get('maxlengthtext');
		$this->fieldInfo['maxwidthcolumn'] = $this->get('maxwidthcolumn');
		$this->fieldInfo['defaultvalue'] = $this->hasDefaultValue();
		$this->fieldInfo['type'] = $fieldDataType;
		$this->fieldInfo['name'] = $this->get('name');
		$this->fieldInfo['label'] = \App\Runtime\Vtiger_Language_Handler::translate($this->get('label'), $this->getModuleName());

		switch ($fieldDataType) {
			case 'picklist' :
			case 'multipicklist':
			case 'multiowner':
			case 'multiReferenceValue':
			case 'inventoryLimit':
			case 'languages':
			case 'currencyList':
			case 'fileLocationType':
			case 'taxes':
				$pickListValues = $this->getPicklistValues();
				if (!empty($pickListValues)) {
					$this->fieldInfo['picklistvalues'] = $pickListValues;
				} else {
					$this->fieldInfo['picklistvalues'] = [];
				}
				break;
			case 'date':
			case 'datetime':
				$this->fieldInfo['date-format'] = $currentUser->get('date_format');
				break;
			case 'time':
				$this->fieldInfo['time-format'] = $currentUser->get('hour_format');
				break;
			case 'currency':
				$this->fieldInfo['currency_symbol'] = $currentUser->get('currency_symbol');
				$this->fieldInfo['decimal_separator'] = $currentUser->get('currency_decimal_separator');
				$this->fieldInfo['group_separator'] = $currentUser->get('currency_grouping_separator');
				break;
		case 'owner':
		case 'userCreator':
		case 'sharedOwner':
			$module = $context['module'] ?? null;
			$mode = $context['mode'] ?? null;
			
			if (!\App\Core\AppConfig::performance('SEARCH_OWNERS_BY_AJAX') || in_array($module, ['CustomView', 'Workflows', 'Template', 'MappedFields', 'DataAccess', 'Reports']) || $mode === 'showAdvancedSearch') {
				$userList = \App\Fields\Owner::getInstance($this->getModuleName(), $currentUser)->getAccessibleUsers('', $fieldDataType);
				$groupList = \App\Fields\Owner::getInstance($this->getModuleName(), $currentUser)->getAccessibleGroups('', $fieldDataType);
				$pickListValues = [];
				$pickListValues[\App\Runtime\Vtiger_Language_Handler::translate('LBL_USERS', $this->getModuleName())] = $userList;
				$pickListValues[\App\Runtime\Vtiger_Language_Handler::translate('LBL_GROUPS', $this->getModuleName())] = $groupList;
				$this->fieldInfo['picklistvalues'] = $pickListValues;
				if (\App\Core\AppConfig::performance('SEARCH_OWNERS_BY_AJAX')) {
					$this->fieldInfo['searchOperator'] = 'e';
				}
			} else {
				if ($fieldDataType == 'owner') {
					$this->fieldInfo['searchOperator'] = 'e';
				}
			}
			break;
			case 'modules':
				$modulesList = [];
				foreach ($this->getModulesListValues() as $moduleId => $module) {
					$modulesList[$module['name']] = $module['label'];
				}
				$this->fieldInfo['picklistvalues'] = $modulesList;
				break;
			case 'categoryMultipicklist':
			case 'tree':
				$tree = $this->getUITypeModel()->getAllValue();
				$pickListValues = [];
				foreach ($tree as $key => $labels) {
					$pickListValues[$key] = $labels[0];
				}
				$this->fieldInfo['picklistvalues'] = $pickListValues;
				break;
			case 'email':
				if (\App\Core\AppConfig::security('RESTRICTED_DOMAINS_ACTIVE') && !empty(\App\Core\AppConfig::security('RESTRICTED_DOMAINS_VALUES'))) {
					$validate = false;
					if (empty(\App\Core\AppConfig::security('RESTRICTED_DOMAINS_ALLOWED')) || in_array($this->getModuleName(), \App\Core\AppConfig::security('RESTRICTED_DOMAINS_ALLOWED'))) {
						$validate = true;
					}
					if (in_array($this->getModuleName(), \App\Core\AppConfig::security('RESTRICTED_DOMAINS_EXCLUDED'))) {
						$validate = false;
					}
					if ($validate) {
						$this->fieldInfo['restrictedDomains'] = \App\Core\AppConfig::security('RESTRICTED_DOMAINS_VALUES');
					}
				}
				break;
		}

		if (in_array($fieldDataType, \App\Modules\Base\Models\Field::$referenceTypes) && \App\Core\AppConfig::performance('SEARCH_REFERENCE_BY_AJAX')) {
			$this->fieldInfo['searchOperator'] = 'e';
		}
		return $this->fieldInfo;
	}

	public function setFieldInfo($fieldInfo)
	{
		$this->fieldInfo = $fieldInfo;
	}

	/**
	 * Function to get the advanced filter option names by Field type
	 * @return <Array>
	 */
	public static function getAdvancedFilterOpsByFieldType()
	{
		return array(
			'V' => ['e', 'n', 's', 'ew', 'c', 'k', 'y', 'ny', 'om', 'wr', 'nwr'],
			'N' => ['e', 'n', 'l', 'g', 'm', 'h', 'y', 'ny'],
			'T' => ['e', 'n', 'l', 'g', 'm', 'h', 'bw', 'b', 'a', 'y', 'ny'],
			'I' => ['e', 'n', 'l', 'g', 'm', 'h', 'y', 'ny'],
			'C' => ['e', 'n', 'y', 'ny'],
			'D' => ['e', 'n', 'bw', 'b', 'a', 'y', 'ny'],
			'DT' => ['e', 'n', 'bw', 'b', 'a', 'y', 'ny'],
			'NN' => ['e', 'n', 'l', 'g', 'm', 'h', 'y', 'ny'],
			'E' => ['e', 'n', 's', 'ew', 'c', 'k', 'y', 'ny']
		);
	}

	/**
	 * Function to retrieve field models for a module, grouped by block FK.
	 * Returns array keyed by block ID (0 = no block).
	 *
	 * @param \App\Modules\Base\Models\Module|\vtlib\Module $moduleModel
	 * @return array<int, static[]>
	 */
	public static function getAllForModule($moduleModel)
	{
		$fieldModelList = \App\Cache\Cache::get('ModuleFields', $moduleModel->id);
		if ($fieldModelList) {
			return $fieldModelList;
		}

		if (method_exists($moduleModel, 'getName')) {
			$moduleName = $moduleModel->getName();
		} else {
			$moduleName = $moduleModel->name ?? \App\Utils\ModuleUtils::getModuleName($moduleModel->id) ?: 'Base';
		}

		$rows = (new \App\Db\Query())
			->from('vtiger_field')
			->where(['tabid' => $moduleModel->id])
			->orderBy(['block' => SORT_ASC, 'sequence' => SORT_ASC])
			->all();

		$fieldModelList = [];
		foreach ($rows as $row) {
			$className = \App\Core\Loader::getComponentClassName('Model', 'Field', $moduleName);
			$fieldModelObject = $className::fromRow($row);
			$blockId = $fieldModelObject->getDefinition()?->block ?? 0;
			$fieldModelList[$blockId][] = $fieldModelObject;
			\App\Cache\Cache::save('field-' . $moduleModel->getId(), $fieldModelObject->getId(), $fieldModelObject);
			\App\Cache\Cache::save('field-' . $moduleModel->getId(), $fieldModelObject->getName(), $fieldModelObject);
		}

		\App\Cache\Cache::save('ModuleFields', $moduleModel->id, $fieldModelList);
		return $fieldModelList;
	}

	/**
	 * Returns all fields for a block, in sequence order.
	 *
	 * @param \App\Modules\Base\Models\Block|\vtlib\Block $blockInstance
	 * @param \App\Modules\Base\Models\Module|\vtlib\Module|null $moduleInstance
	 * @return static[]
	 */
	public static function getAllForBlock($blockInstance, $moduleInstance = null)
	{
		$blockId = is_object($blockInstance) ? ($blockInstance->id ?? $blockInstance->blockid ?? null) : (int) $blockInstance;
		if (!$blockId) {
			return [];
		}

		$moduleName = null;
		if ($moduleInstance !== null) {
			$moduleName = method_exists($moduleInstance, 'getName')
				? $moduleInstance->getName()
				: ($moduleInstance->name ?? null);
		}
		if (!$moduleName) {
			$tabid = (new \App\Db\Query())
				->select('tabid')->from('vtiger_blocks')
				->where(['blockid' => $blockId])->scalar();
			$moduleName = $tabid ? (\App\Utils\ModuleUtils::getModuleName((int) $tabid) ?: 'Base') : 'Base';
		}

		$rows = (new \App\Db\Query())
			->from('vtiger_field')
			->where(['block' => $blockId])
			->orderBy(['sequence' => SORT_ASC])
			->all();

		$instances = [];
		foreach ($rows as $row) {
			$className = \App\Core\Loader::getComponentClassName('Model', 'Field', $moduleName);
			$instances[] = $className::fromRow($row);
		}
		return $instances;
	}

	/**
	 * Function to get instance
	 * @param string|int $value - fieldname or fieldid
	 * @param \App\Modules\Base\Models\Module|false $module - optional module instance
	 * @return static|false
	 */
	public static function getInstance($value, $module = false)
	{
		if ($module) {
			$cached = \App\Cache\Cache::get('field-' . $module->getId(), $value);
			if ($cached) {
				return $cached;
			}
		}

		$query = (new \App\Db\Query())->from('vtiger_field');
		if (is_numeric($value)) {
			$query->where(['fieldid' => (int) $value]);
		} else {
			$query->where(['fieldname' => $value]);
			if ($module) {
				$query->andWhere(['tabid' => $module->getId()]);
			}
		}
		$row = $query->one();
		if (!$row) {
			return false;
		}

		$moduleName = \App\Utils\ModuleUtils::getModuleName((int) $row['tabid']) ?: 'Base';
		$className = \App\Core\Loader::getComponentClassName('Model', 'Field', $moduleName);
		$fieldModel = $className::fromRow($row);

		if ($module) {
			\App\Cache\Cache::save('field-' . $module->getId(), $value, $fieldModel);
		}
		return $fieldModel;
	}

	/**
	 * Deletes all fields and their associated profile/relation data for a module.
	 * Called during module deletion.
	 *
	 * @param \App\Modules\Base\Models\Module|\vtlib\Module $moduleInstance
	 */
	public static function deleteForModule($moduleInstance): void
	{
		$db = \App\Db\Db::getInstance();
		$moduleId = is_object($moduleInstance) ? (int) $moduleInstance->id : (int) $moduleInstance;

		$fieldIds = (new \App\Db\Query())
			->select(['fieldid'])
			->from('vtiger_field')
			->where(['tabid' => $moduleId])
			->column();

		foreach ($fieldIds as $fieldId) {
			$db->createCommand()->delete('vtiger_profile2field', ['fieldid' => $fieldId])->execute();
			$db->createCommand()->delete('vtiger_fieldmodulerel', ['fieldid' => $fieldId])->execute();
		}

		$db->createCommand()->delete('vtiger_field', ['tabid' => $moduleId])->execute();
		\vtlib\Utils::Log('Deleting fields of the module ... DONE');
	}

	/**
	 * Added function that returns the folders in a Document
	 * @return <Array>
	 */
	public function getDocumentFolders()
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$result = $adb->pquery("SELECT `tree`,`name` FROM
				`vtiger_trees_templates_data` 
			INNER JOIN `vtiger_field` 
				ON `vtiger_trees_templates_data`.`templateid` = `vtiger_field`.`fieldparams` 
			WHERE `vtiger_field`.`columnname` = ? 
				AND `vtiger_field`.`tablename` = ?;", array('folderid', 'vtiger_notes'));
		$rows = $adb->num_rows($result);
		$folders = [];
		for ($i = 0; $i < $rows; $i++) {
			$folderId = $adb->query_result($result, $i, 'tree');
			$folderName = $adb->query_result($result, $i, 'name');
			$folders[$folderId] = $folderName;
		}
		return $folders;
	}

	/**
	 * Function checks if the current Field is Read/Write
	 * @return boolean
	 */
	public function getProfileReadWritePermission()
	{
		return $this->getPermissions(false);
	}

	/**
	 * Function returns Client Side Validators name
	 * @return <Array> [name=>Name of the Validator, params=>Extra Parameters]
	 */
	public function getValidator(): array
	{
		$validator = [];

		static $singleFieldValidators = [
			'birthday'     => ['name' => 'lessThanToday'],
			'qty_per_unit' => ['name' => 'PositiveNumber'],
			'qtyindemand'  => ['name' => 'PositiveNumber'],
			'hours'        => ['name' => 'PositiveNumber'],
			'days'         => ['name' => 'PositiveNumber'],
			'employees'    => ['name' => 'WholeNumber'],
			'related_to'   => ['name' => 'ReferenceField'],
		];

		$fieldName = $this->getName();
		if (isset($singleFieldValidators[$fieldName])) {
			$validator[] = $singleFieldValidators[$fieldName];
		}

		static $operatorMap = [
			'GE' => 'greaterThanDependentField',
			'G'  => 'greaterThanDependentField',
			'LE' => 'lessThanDependentField',
			'L'  => 'lessThanDependentField',
		];

		foreach (self::getFieldConstraints((int) $this->get('id')) as $row) {
			$validator[] = [
				'name'   => $operatorMap[$row['operator']],
				'params' => [$row['ref_fieldname']],
			];
		}

		return $validator;
	}

	/**
	 * Returns cross-field constraints for the given fieldid from vtiger_field_constraints.
	 * Loaded once per request and cached in memory.
	 */
	private static function getFieldConstraints(int $fieldId): array
	{
		static $cache = null;
		if ($cache === null) {
			$cache = [];
			$rows = (new \App\Db\Query())
				->select(['fieldid', 'operator', 'ref_fieldname'])
				->from('vtiger_field_constraints')
				->all();
			foreach ($rows as $row) {
				$cache[(int) $row['fieldid']][] = $row;
			}
		}
		return $cache[$fieldId] ?? [];
	}

	/**
	 * Function to retrieve display value in edit view
	 * @param string $value - value which need to be converted to display value
	 * @return string - converted display value
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		return $this->getUITypeModel()->getEditViewDisplayValue($value, $record);
	}

	/**
	 * Function returns list of Currencies available in the system
	 * @return array
	 */
	public function getCurrencyList()
	{
		if (\App\Cache\Cache::has('Currency', 'List')) {
			return \App\Cache\Cache::get('Currency', 'List');
		}
		$currencies = (new \App\Db\Query())->select('id, currency_name')
				->from('vtiger_currency_info')
				->where(['currency_status' => 'Active', 'deleted' => 0])
				->createCommand()->queryAllByGroup();
		asort($currencies);
		\App\Cache\Cache::save('Currency', 'List', $currencies, \App\Cache\Cache::LONG);
		return $currencies;
	}

	/**
	 * Function to get Display value for RelatedList
	 * @param string $value
	 * @return string
	 */
	public function getRelatedListDisplayValue($value)
	{
		return $this->getUITypeModel()->getRelatedListDisplayValue($value);
	}

	/**
	 * Function to get Default Field Value
	 * @return string defaultvalue
	 */
	public function getDefaultFieldValue()
	{
		return $this->definition?->defaultvalue ?? '';
	}

	/**
	 * Function whcih will get the databse insert value format from user format
	 * @param mixed $value in user format
	 * @return mixed
	 */
	public function getDBValue($value)
	{
		return $this->getUITypeModel()->getDBValue($value);
	}

	/**
	 * Function to get visibilty permissions of a Field
	 * @param boolean $readOnly
	 * @return boolean
	 */
	public function getPermissions($readOnly = true)
	{
		return \App\Fields\Field::getFieldPermission($this->getModuleId(), $this->getName(), $readOnly);
	}

	/**
	 * Creates a new field in vtiger_field, adds the DB column, initialises profile
	 * access, and returns a hydrated Field instance. Runs in a single transaction.
	 */
	public static function create(int $moduleId, int $blockId, FieldDefinition $def): static
	{
		$db = \App\Db\Db::getInstance();
		$transaction = $db->beginTransaction();
		try {
			$fieldId = $db->getUniqueID('vtiger_field');

			$sequence = $def->sequence;
			if (!$sequence) {
				$maxSeq = (new \App\Db\Query())
					->from('vtiger_field')
					->where(['tabid' => $moduleId, 'block' => $blockId])
					->max('sequence');
				$sequence = $maxSeq ? (int) $maxSeq + 1 : 0;
			}

			$quickcreatesequence = $def->quickcreatesequence;
			if ($def->quickcreate != 1 && $quickcreatesequence === null) {
				$maxSeq = (new \App\Db\Query())
					->from('vtiger_field')
					->where(['tabid' => $moduleId])
					->max('quickcreatesequence');
				$quickcreatesequence = $maxSeq ? (int) $maxSeq + 1 : 0;
			}

			$table = $def->table;
			if (!$table) {
				$table = (string) ((new \App\Db\Query())
					->select('basetable')
					->from('vtiger_tab')
					->where(['tabid' => $moduleId])
					->scalar() ?: '');
			}
			$column   = $def->column ?: strtolower($def->name);
			$label    = $def->label  ?: $def->name;
			$maximumlength = max(0, min(65535, $def->maximumlength));

			$def = $def->with([
				'id'                  => $fieldId,
				'tabid'               => $moduleId,
				'table'               => $table,
				'column'              => $column,
				'label'               => $label,
				'sequence'            => $sequence,
				'block'               => $blockId,
				'quickcreatesequence' => $quickcreatesequence,
				'maximumlength'       => $maximumlength,
			]);

			$row = $def->toRow();
			// toRow() returns 'fieldid'; INSERT uses it as the PK value
			$db->createCommand()->insert('vtiger_field', $row)->execute();

			\App\ModuleManagement\ServiceLocator::getProfileService()->initForField($moduleId, $fieldId);

			$columntype = $def->columntype;
			if ($columntype) {
				$tableSchema = $db->getSchema()->getTableSchema($table, true);
				if ($tableSchema && is_null($tableSchema->getColumn($column))) {
					if (is_array($columntype)) {
						$columntype = $db->getSchema()->createColumnSchemaBuilder($columntype[0], $columntype[1]);
					}
					$db->createCommand()->addColumn($table, $column, $columntype)->execute();
				}
				if ($def->uitype === 10) {
					$indexName = "{$table}_{$column}_idx";
					$indexExists = (new \App\Db\Query())
						->from('INFORMATION_SCHEMA.STATISTICS')
						->where([
							'TABLE_SCHEMA' => $db->createCommand('SELECT DATABASE()')->queryScalar(),
							'TABLE_NAME'   => $table,
							'INDEX_NAME'   => $indexName,
						])->exists();
					if (!$indexExists) {
						try {
							$db->createCommand()->createIndex($indexName, $table, $column)->execute();
						} catch (\Exception $e) {
							if (strpos($e->getMessage(), 'Duplicate key') === false) {
								throw $e;
							}
						}
					}
				}
			}

			$transaction->commit();

			$instance = static::fromRow($def->toRow());
			// Bust caches so the new field is visible immediately
			\App\Cache\Cache::delete('ModuleFields', $moduleId);
			\App\Cache\Cache::delete('fieldInfo', $moduleId);
			\App\Fields\Field::clearFieldsPermissionsCacheForTab($moduleId);
			return $instance;
		} catch (\Exception $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	/**
	 * Deletes this field from vtiger_field along with its profile and relation data.
	 * Invalidates all relevant caches.
	 */
	public function delete(): void
	{
		if ($this->definition === null) {
			return;
		}
		$fieldId  = $this->definition->id;
		$moduleId = $this->definition->tabid;
		$fieldName = $this->definition->name;

		$db = \App\Db\Db::getInstance();
		$db->createCommand()->delete('vtiger_profile2field', ['fieldid' => $fieldId])->execute();
		if ($this->definition->uitype === 10) {
			$db->createCommand()->delete('vtiger_fieldmodulerel', ['fieldid' => $fieldId])->execute();
		}
		$db->createCommand()->delete('vtiger_field', ['fieldid' => $fieldId])->execute();

		if ($moduleId) {
			\App\Cache\Cache::delete('ModuleFields', $moduleId);
			\App\Cache\Cache::delete('fieldInfo', $moduleId);
			\App\Fields\Field::clearFieldsPermissionsCacheForTab($moduleId);
			\App\Cache\Cache::delete('field-' . $moduleId, $fieldId);
			if ($fieldName !== '') {
				\App\Cache\Cache::delete('field-' . $moduleId, $fieldName);
				if (isset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[$moduleId][$fieldName])) {
					unset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[$moduleId][$fieldName]);
				}
			}
		}
		\App\Cache\Cache::delete('FieldModel', $fieldId);
	}

	/**
	 * Inserts vtiger_fieldmodulerel rows for UIType 10 reference fields.
	 */
	public function setRelatedModules(array $moduleNames): void
	{
		if ($this->definition === null || empty($moduleNames)) {
			return;
		}
		$fieldId    = $this->definition->id;
		$moduleName = $this->getModuleName() ?? '';
		$db = \App\Db\Db::getInstance();
		foreach ($moduleNames as $relmodule) {
			$exists = (new \App\Db\Query())
				->from('vtiger_fieldmodulerel')
				->where(['fieldid' => $fieldId, 'module' => $moduleName, 'relmodule' => $relmodule])
				->exists();
			if (!$exists) {
				$db->createCommand()->insert('vtiger_fieldmodulerel', [
					'fieldid'   => $fieldId,
					'module'    => $moduleName,
					'relmodule' => $relmodule,
				])->execute();
			}
		}
	}

	/**
	 * Removes vtiger_fieldmodulerel rows for UIType 10 reference fields.
	 */
	public function unsetRelatedModules(array $moduleNames): void
	{
		if ($this->definition === null || empty($moduleNames)) {
			return;
		}
		\App\Db\Db::getInstance()->createCommand()->delete('vtiger_fieldmodulerel', [
			'fieldid'   => $this->definition->id,
			'relmodule' => $moduleNames,
		])->execute();
	}

	/**
	 * Create / extend picklist table and add values for this field.
	 * Handles both role-based (uitype=15) and non-role-based (uitype=16) picklists.
	 */
	public function setPicklistValues(array $values): void
	{
		if ($this->definition === null || empty($values)) {
			return;
		}
		$fieldName = $this->definition->name;
		$uitype    = $this->definition->uitype;
		$db = \App\Db\Db::getInstance();

		$picklistTable = 'vtiger_' . $fieldName;

		// Non-role picklist (uitype 16)
		if ($uitype === 16) {
			$pickListNameIDs = ['recurring_frequency', 'payment_duration'];
			$picklistIdCol = in_array($fieldName, $pickListNameIDs) ? $fieldName . '_id' : $fieldName . 'id';

			if (!$db->isTableExists($picklistTable)) {
				$importer = new \App\Db\Importers\Base();
				$db->createTable($picklistTable, [
					$picklistIdCol   => 'pk',
					$fieldName       => 'string',
					'presence'       => $importer->boolean()->defaultValue(true),
					'sortorderid'    => $importer->smallInteger()->defaultValue(0),
				]);
			}

			$existing = \App\Fields\Picklist::getPickListValues($fieldName);
			$sortid = 1;
			foreach ($values as $value) {
				if (in_array($value, $existing)) {
					continue;
				}
				$db->createCommand()->insert($picklistTable, [
					$fieldName    => $value,
					'sortorderid' => $sortid,
					'presence'    => 1,
				])->execute();
				++$sortid;
			}
			return;
		}

		// Role-based picklist (uitype 15 and similar)
		$picklistIdCol = $fieldName . 'id';

		if (!$db->isTableExists($picklistTable)) {
			$importer = new \App\Db\Importers\Base();
			$db->createTable($picklistTable, [
				$picklistIdCol      => 'pk',
				$fieldName          => 'string',
				'presence'          => $importer->boolean()->defaultValue(true),
				'picklist_valueid'  => $importer->smallInteger()->defaultValue(0),
				'sortorderid'       => $importer->smallInteger()->defaultValue(0),
			]);
			$db->createCommand()->insert('vtiger_picklist', ['name' => $fieldName])->execute();
			$newPicklistId = $db->getLastInsertID('vtiger_picklist_picklistid_seq');
		} else {
			$newPicklistId = (new \App\Db\Query())
				->select(['picklistid'])
				->from('vtiger_picklist')
				->where(['name' => $fieldName])
				->scalar();
		}

		// Some picklists use non-standard ID column names
		$specialNameSpacedPicklists = ['opportunity_type' => 'opptypeid', 'duration_minutes' => 'minutesid'];
		$tableSchema = $db->getSchema()->getTableSchema($picklistTable, true);
		if ($tableSchema && $tableSchema->getColumn($fieldName . '_id')) {
			$picklistIdCol = $fieldName . '_id';
		} elseif (array_key_exists($fieldName, $specialNameSpacedPicklists)) {
			$picklistIdCol = $specialNameSpacedPicklists[$fieldName];
		}

		$existing = \App\Fields\Picklist::getPickListValues($fieldName);
		$sortid = 0;
		foreach ($values as $value) {
			if (in_array($value, $existing)) {
				continue;
			}
			$newPicklistValueId = $db->getUniqueID('vtiger_picklistvalues');
			++$sortid;
			$db->createCommand()->insert($picklistTable, [
				$fieldName          => $value,
				'presence'          => 1,
				'picklist_valueid'  => $newPicklistValueId,
				'sortorderid'       => $sortid,
			])->execute();

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

	public function __update()
	{
		if ($this->definition === null) {
			return;
		}
		$db = \App\Db\Db::getInstance();
		$row = $this->definition->toRow();
		// fieldid and tabid are structural — not updated via __update
		unset($row['fieldid'], $row['tabid'], $row['fieldname'], $row['columnname'], $row['tablename']);
		$db->createCommand()->update('vtiger_field', $row, ['fieldid' => $this->definition->id])->execute();
		if ($this->definition->mandatory) {
			$blockId = (int) $this->getBlockId();
			if ($blockId) {
				$db->createCommand()->update('vtiger_blocks_hide', ['enabled' => 0], ['blockid' => $blockId])->execute();
			}
		}
	}

	/**
	 * Persist field metadata changes.
	 *
	 * Historically some settings actions call `save()` on the field model.
	 * This wrapper delegates to `__update()` and clears field caches.
	 */
	public function save(): self
	{
		$this->__update();

		$moduleId = (int) $this->getModuleId();
		$fieldId = (int) $this->getId();
		$fieldName = (string) $this->getName();

		if ($moduleId) {
			\App\Cache\Cache::delete('ModuleFields', $moduleId);
			\App\Cache\Cache::delete('fieldInfo', $moduleId);
			\App\Fields\Field::clearFieldsPermissionsCacheForTab($moduleId);
			if ($fieldId) {
				\App\Cache\Cache::delete('field-' . $moduleId, $fieldId);
			}
			if ($fieldName !== '') {
				\App\Cache\Cache::delete('field-' . $moduleId, $fieldName);
				if (isset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[$moduleId][$fieldName])) {
					unset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[$moduleId][$fieldName]);
				}
			}
		}
		\App\Cache\Cache::delete('FieldModel', $fieldId);

		return $this;
	}

	public function updateMandatory($mandatoryValue = 'O'): self
	{
		$mandatoryValue = strtoupper($mandatoryValue);
		if ($mandatoryValue !== 'M' && $mandatoryValue !== 'O') {
			return $this;
		}
		if ($this->definition !== null) {
			$this->definition = $this->definition->with(['mandatory' => $mandatoryValue === 'M']);
		}
		return $this;
	}

	public function isCustomField()
	{
		return ($this->definition?->generatedtype == 2) ? true : false;
	}

	public function hasDefaultValue()
	{
		return ($this->definition?->defaultvalue ?? '') == '' ? false : true;
	}

	public function isActiveField()
	{
		$presence = $this->get('presence');
		return in_array($presence, array(0, 2));
	}

	public function isMassEditable()
	{
		return ($this->definition?->masseditable ?? 0) == 1 ? true : false;
	}

	public function isHeaderField()
	{
		return !empty($this->definition?->header_field) ? true : false;
	}

	/**
	 * Function which will check if empty piclist option should be given
	 * @return boolean
	 */
	public function isEmptyPicklistOptionAllowed()
	{
		if (method_exists($this->getUITypeModel(), 'isEmptyPicklistOptionAllowed')) {
			return $this->getUITypeModel()->isEmptyPicklistOptionAllowed();
		}
		return true;
	}

	public function isReferenceField()
	{
		return in_array($this->getFieldDataType(), self::$referenceTypes);
	}

	public function isOwnerField()
	{
		return ($this->getFieldDataType() == self::OWNER_TYPE) ? true : false;
	}

	/**
	 * Function returns field instance for field ID
	 * @param int $fieldId
	 * @param int $moduleTabId
	 * @return static|false
	 */
	public static function getInstanceFromFieldId($fieldId, $moduleTabId = false)
	{
		$fieldModel = \App\Cache\Cache::get('FieldModel', $fieldId);
		if ($fieldModel) {
			return $fieldModel;
		}
		$row = \App\Fields\Field::getFieldInfo($fieldId);
		if (!$row) {
			return false;
		}
		$moduleName = \App\Utils\ModuleUtils::getModuleName($row['tabid']) ?: 'Base';
		$className = \App\Core\Loader::getComponentClassName('Model', 'Field', $moduleName);
		$fieldModel = $className::fromRow($row);
		\App\Cache\Cache::save('FieldModel', $fieldId, $fieldModel);
		return $fieldModel;
	}

	public function getWithDefaultValue()
	{
		$defaultValue = $this->getDefaultFieldValue();
		$recordValue = $this->get('fieldvalue');

		if (empty($recordValue) && !empty($defaultValue))
			$this->set('fieldvalue', $defaultValue);
		return $this;
	}

	public function getFieldParams()
	{
		return \App\Utils\Json::decode($this->get('fieldparams'));
	}

	public function isActiveSearchView()
	{
		if ($this->get('fromOutsideList')) {
			return false;
		}
		return $this->getUITypeModel()->isActiveSearchView();
	}

	/**
	 * Function returns info about field structure in database
	 * @param boolean $returnString
	 * @return string|array
	 */
	public function getDBColumnType($returnString = true)
	{
		$db = \App\Db\Db::getInstance();
		$tableSchema = $db->getSchema()->getTableSchema($this->getTableName());
		$columnSchema = $tableSchema->getColumn($this->getColumnName());
		$data = get_object_vars($columnSchema);
		if ($returnString) {
			$string = $data['type'];
			if ($data['size']) {
				if ($data['type'] === 'decimal') {
					$string .= '(' . $data['size'] . ',' . $data['scale'] . ')';
				} else {
					$string .= '(' . $data['size'] . ')';
				}
			}
			return $string;
		}
		return $data;
	}
}
