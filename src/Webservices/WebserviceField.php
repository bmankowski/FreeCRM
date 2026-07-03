<?php
/* +*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * **************************************************************************** */
namespace App\Webservices;

class WebserviceField
{

	private $fieldId;
	private $uitype;
	private $blockId;
	private $blockName;
	private $nullable;
	private $default;
	private $tableName;
	private $columnName;
	private $fieldName;
	private $fieldLabel;
	private $editable;
	private $fieldType;
	private $displayType;
	private $mandatory;
	private $massEditable;
	private $tabid;
	private $presence;
	private $fieldparams;

	/**
	 *
	 * @var \App\Database\PearDatabase
	 */
	private $typeOfData;
	private $fieldDataType;
	private $dataFromMeta;
	private static $tableMeta = [];
	private static $fieldTypeMapping = [];
	private $referenceList;
	private $defaultValuePresent;
	private $explicitDefaultValue;
	private $genericUIType = 10;
	private $readOnly = 0;

	public function __construct($row)
	{
		$this->uitype = $row['uitype'];
		$this->blockId = $row['block'];
		$this->blockName = null;
		$this->tableName = $row['tablename'];
		$this->columnName = $row['columnname'];
		$this->fieldName = $row['fieldname'];
		$this->fieldLabel = $row['fieldlabel'];
		$this->displayType = $row['displaytype'];
		$this->massEditable = ($row['masseditable'] === '1') ? true : false;
		$this->typeOfData = \App\Field\FieldDefinition::normalizeTypeofdata((string) $row['typeofdata']);
		$this->presence = $row['presence'];
		$this->mandatory = (bool) ($row['mandatory'] ?? false);
		if ($this->uitype == 4) {
			$this->mandatory = false;
		}
		$this->fieldType = $this->typeOfData;
		$this->tabid = $row['tabid'];
		$this->fieldId = $row['fieldid'];
		$this->fieldDataType = null;
		$this->dataFromMeta = false;
		$this->defaultValuePresent = false;
		$this->referenceList = null;
		$this->explicitDefaultValue = false;
		$this->fieldparams = $row['fieldparams'];
		$this->readOnly = (isset($row['readonly'])) ? $row['readonly'] : 0;

		if (isset($row['defaultvalue'])) {
			$this->setDefault($row['defaultvalue']);
		}
	}

	public static function fromArray($adb, $row)
	{
		return new WebserviceField($row);
	}

	public function getTableName()
	{
		return $this->tableName;
	}

	public function getFieldName()
	{
		return $this->fieldName;
	}

	public function getFieldLabelKey()
	{
		return $this->fieldLabel;
	}

	public function getFieldType()
	{
		return $this->fieldType;
	}

	public function isMandatory()
	{
		return $this->mandatory;
	}

	public function getTypeOfData()
	{
		return $this->typeOfData;
	}

	public function getDisplayType()
	{
		return $this->displayType;
	}

	public function getMassEditable()
	{
		return $this->massEditable;
	}

	public function getFieldId()
	{
		return $this->fieldId;
	}

	public function getDefault()
	{
		if ($this->dataFromMeta !== true && $this->explicitDefaultValue !== true) {
			$this->fillColumnMeta();
		}
		return $this->default;
	}

	public function getColumnName()
	{
		return $this->columnName;
	}

	public function getBlockId()
	{
		return $this->blockId;
	}

	public function getBlockName()
	{
		if (empty($this->blockName)) {
			$this->blockName = \App\Utils\Utils::getBlockName($this->blockId);
		}
		return $this->blockName;
	}

	public function getTabId()
	{
		return $this->tabid;
	}

	public function isNullable()
	{
		if ($this->dataFromMeta !== true) {
			$this->fillColumnMeta();
		}
		return $this->nullable;
	}

	public function hasDefault()
	{
		if ($this->dataFromMeta !== true && $this->explicitDefaultValue !== true) {
			$this->fillColumnMeta();
		}
		return $this->defaultValuePresent;
	}

	public function getUIType()
	{
		return $this->uitype;
	}

	public function getFieldParams()
	{
		return \App\Utils\Json::decode($this->fieldparams);
	}

	public function isReadOnly()
	{
		if ($this->readOnly == 1)
			return true;
		return false;
	}

	private function setNullable($nullable)
	{
		$this->nullable = $nullable;
	}

	public function setDefault($value)
	{
		$this->default = $value;
		$this->explicitDefaultValue = true;
		$this->defaultValuePresent = true;
	}

	public function setFieldDataType($dataType)
	{
		$this->fieldDataType = $dataType;
	}

	public function setReferenceList($referenceList)
	{
		$this->referenceList = $referenceList;
	}

	public function getTableFields()
	{
		$tableFields = null;
		if (isset(\App\Webservices\WebserviceField::$tableMeta[$this->getTableName()])) {
			$tableFields = \App\Webservices\WebserviceField::$tableMeta[$this->getTableName()];
		} else {
			$dbMetaColumns = \App\Database\PearDatabase::getInstance()->getColumnsMeta($this->getTableName());
			$tableFields = [];
			foreach ($dbMetaColumns as $key => $dbField) {
				$tableFields[$dbField->name] = $dbField;
			}
			\App\Webservices\WebserviceField::$tableMeta[$this->getTableName()] = $tableFields;
		}
		return $tableFields;
	}

	public function fillColumnMeta()
	{
		$tableFields = $this->getTableFields();
		foreach ($tableFields as $fieldName => $dbField) {
			if (strcmp($fieldName, $this->getColumnName()) === 0) {
				$this->setNullable(!$dbField->notNull);
				if ($dbField->hasDefault === true && !$this->explicitDefaultValue) {
					$this->defaultValuePresent = $dbField->hasDefault;
					$this->setDefault($dbField->default);
				}
			}
		}
		$this->dataFromMeta = true;
	}

	/**
	 * Returns the field type for REST API consumers.
	 * Source: vtiger_ws_fieldtype (by uitype) with typeofdata as fallback.
	 * Note: 'V' typeofdata maps to 'string' here — correct for the API,
	 * but wrong for UI rendering. Use Field::getUiTypeName() for UI/business logic.
	 */
	public function getFieldDataType()
	{
		if ($this->fieldDataType === null) {
			$fieldDataType = $this->getFieldTypeFromUIType();
			if ($fieldDataType === null) {
				$fieldDataType = $this->getFieldTypeFromTypeOfData();
			}
			$this->fieldDataType = $fieldDataType;
		}
		return $this->fieldDataType;
	}

	public function getReferenceList()
	{
		if ($this->referenceList === null) {
			if (\App\Cache\Cache::has('getReferenceList', $this->getFieldId())) {
				return \App\Cache\Cache::get('getReferenceList', $this->getFieldId());
			}
			if (!isset(\App\Webservices\WebserviceField::$fieldTypeMapping[$this->getUIType()])) {
				$this->getFieldTypeFromUIType();
			}
			$fieldTypeData = \App\Webservices\WebserviceField::$fieldTypeMapping[$this->getUIType()];
			$currentUser = \App\User\CurrentUser::get();
			$types = vtws_listtypes(null, $current_user);

			$accessibleTypes = $types['types'];
			//If it is non admin user or the edit and view is there for profile then users module will be accessible
			if (!\vtlib\Functions:: userIsAdministrator($current_user) && !in_array("Users", $accessibleTypes)) {
				array_push($accessibleTypes, 'Users');
			}

			$referenceTypes = [];
			if (!in_array($this->getUIType(), [66, 67, 68])) {
				if ($this->getUIType() != $this->genericUIType) {
					$query = (new \App\Db\Query())->select('vtiger_ws_referencetype.type')
						->from('vtiger_ws_referencetype')
						->innerJoin('vtiger_tab', 'vtiger_tab.name = vtiger_ws_referencetype.type')
						->where(['fieldtypeid' => $fieldTypeData['fieldtypeid']])
						->andWhere(['not in', 'vtiger_tab.presence', [1]]);
				} else {
					$query = (new \App\Db\Query())->select('relmodule as type')
						->from('vtiger_fieldmodulerel')
						->innerJoin('vtiger_tab', 'vtiger_tab.name = vtiger_fieldmodulerel.relmodule')
						->where(['fieldid' => $this->getFieldId()])
						->andWhere(['not in', 'vtiger_tab.presence', [1]])
						->orderBy(['sequence' => SORT_ASC]);
				}
				$dataReader = $query->createCommand()->query();
				while ($row = $dataReader->read()) {
					if (in_array($row['type'], $accessibleTypes))
						array_push($referenceTypes, $row['type']);
				}
			} else {
				$fieldModel = \App\Modules\Base\Models\Field::getInstanceFromFieldId($this->getFieldId());
				$referenceTypes = $fieldModel->getUITypeModel()->getReferenceList();
			}
			$referenceTypesUnsorted = array_values(array_intersect($accessibleTypes, $referenceTypes));

			$referenceTypesSorted = [];
			foreach ($referenceTypesUnsorted as $key => $reference) {
				$keySort = array_search($reference, $referenceTypes);
				$referenceTypesSorted[$keySort] = $reference;
			}
			ksort($referenceTypesSorted);
			\App\Cache\Cache::save('getReferenceList', $this->getFieldId(), $referenceTypesSorted);
			$this->referenceList = $referenceTypesSorted;
			return $referenceTypesSorted;
		}
		return $this->referenceList;
	}

	private function getFieldTypeFromTable()
	{
		$tableFields = $this->getTableFields();
		foreach ($tableFields as $fieldName => $dbField) {
			if (strcmp($fieldName, $this->getColumnName()) === 0) {
				return $dbField->type;
			}
		}
		//This should not be returned if entries in DB are correct.
		return null;
	}

	private function getFieldTypeFromTypeOfData()
	{
		switch ($this->fieldType) {
			case 'T': return 'time';
			case 'D': return 'date';
			case 'DT': return 'datetime';
			case 'E': return 'email';
			case 'N':
			case 'NN': return 'double';
			case 'P': return 'password';
			case 'I': return 'integer';
			case 'V':
			default: return 'string';
		}
	}

	private function getFieldTypeFromUIType()
	{

		// Cache all the information for futher re-use
		if (empty(self::$fieldTypeMapping)) {
			$db = \App\Database\PearDatabase::getInstance();
			$result = $db->pquery('select * from vtiger_ws_fieldtype', []);
			while ($resultrow = $db->fetch_array($result)) {
				self::$fieldTypeMapping[$resultrow['uitype']] = $resultrow;
			}
		}

		if (isset(\App\Webservices\WebserviceField::$fieldTypeMapping[$this->getUIType()])) {
			if (\App\Webservices\WebserviceField::$fieldTypeMapping[$this->getUIType()] === false) {
				return null;
			}
			$row = \App\Webservices\WebserviceField::$fieldTypeMapping[$this->getUIType()];
			return $row['fieldtype'];
		} else {
			\App\Webservices\WebserviceField::$fieldTypeMapping[$this->getUIType()] = false;
			return null;
		}
	}

	public function getPicklistDetails()
	{
		$cacheKey = $this->getTabId() . '-' . $this->getFieldName();
		if (\App\Cache\Cache::has('PicklistDetails', $cacheKey)) {
			return \App\Cache\Cache::get('PicklistDetails', $cacheKey);
		} else {
			$picklistDetails = $this->getPickListOptions($this->getFieldName());
			\App\Cache\Cache::save('PicklistDetails', $cacheKey, $picklistDetails);
			return $picklistDetails;
		}
	}

	public function getPickListOptions()
	{
		$fieldName = $this->getFieldName();
		$db = \App\Database\PearDatabase::getInstance();
		$default_charset = VTWS_PreserveGlobal::getGlobal('default_charset');
		$options = [];
		$sql = "select * from vtiger_picklist where name=?";
		$result = $db->pquery($sql, array($fieldName));
		$numRows = $db->num_rows($result);
		if ($numRows == 0) {
			$sql = "select * from vtiger_$fieldName";
			$result = $db->pquery($sql, []);
			$numRows = $db->num_rows($result);
			for ($i = 0; $i < $numRows; ++$i) {
				$elem = [];
				$picklistValue = $db->query_result($result, $i, $fieldName);
				$picklistValue = \App\Utils\ListViewUtils::decodeHtml($picklistValue);
				$moduleName = \App\Utils\ModuleUtils::getModuleName($this->getTabId());
				if ($moduleName == 'Events')
					$moduleName = 'Calendar';
				$elem["label"] = \App\Runtime\Vtiger_Language_Handler::translate($picklistValue, $moduleName);
				$elem["value"] = $picklistValue;
				array_push($options, $elem);
			}
		}else {
			$user = VTWS_PreserveGlobal::getGlobal('current_user');
			$details = \App\Fields\Picklist::getRoleBasedPicklistValues($fieldName, $user->roleid);
			for ($i = 0; $i < sizeof($details); ++$i) {
				$elem = [];
				$picklistValue = \App\Utils\ListViewUtils::decodeHtml($details[$i]);
				$moduleName = \App\Utils\ModuleUtils::getModuleName($this->getTabId());
				if ($moduleName == 'Events')
					$moduleName = 'Calendar';
				$elem["label"] = \App\Runtime\Vtiger_Language_Handler::translate($picklistValue, $moduleName);
				$elem["value"] = $picklistValue;
				array_push($options, $elem);
			}
		}
		return $options;
	}

	public function getPresence()
	{
		return $this->presence;
	}

	private static $treeDetails = [];

	public function getTreeDetails()
	{
		if (count(self::$treeDetails) > 0) {
			return self::$treeDetails;
		}
		$db = \App\Database\PearDatabase::getInstance();
		$result = $db->pquery('SELECT module FROM vtiger_trees_templates WHERE templateid = ?', [$this->getFieldParams()]);
		$module = $db->getSingleValue($result);
		$moduleName = \App\Utils\ModuleUtils::getModuleName($module);

		$result = $db->pquery('SELECT tree,label FROM vtiger_trees_templates_data WHERE templateid = ?', [$this->getFieldParams()]);
		while ($row = $db->fetch_array($result)) {
			self::$treeDetails[$row['tree']] = \App\Runtime\Vtiger_Language_Handler::translate($row['label'], $moduleName);
		}
		return self::$treeDetails;
	}
}
