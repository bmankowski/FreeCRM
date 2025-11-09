<?php

namespace App\Modules\Base\Models;

/**
 * Basic Inventory Model Class
 * @package YetiForce.Inventory
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class InventoryField extends \App\Runtime\BaseModel
{

	protected $fields = false;
	protected $columns = false;
	protected $jsonFields = ['discountparam', 'taxparam', 'currencyparam'];

	/**
	 * Create the name of the Inventory data table
	 * @param string $module Module name
	 * @param string $prefix Prefix table
	 * @return string Table name
	 */
	public function getTableName($type = 'data')
	{
		switch ($type) {
			case 'data':
				$prefix = '_inventory';
				break;
			case 'fields':
				$prefix = '_invfield';
				break;
			case 'autofield':
				$prefix = '_invmap';
				break;
		}
		$focus = \App\CRMEntity::getInstance($this->get('module'));
		$basetable = $focus->table_name;
		$supfield = $basetable . $prefix;
		return $supfield;
	}

	/**
	 * Loading the Inventory data
	 * @param boolean $returnInBlock Should the result be divided into blocks
	 * @param array $ids
	 * @return array Inventory data
	 */
	public function getFields($returnInBlock = false, $ids = [], $viewType = false)
	{

		\App\Log::trace('Entering ' . __METHOD__ . '| ');
		$key = $returnInBlock ? 'block' : 'noBlock';
		if (!isset($this->fields[$key])) {
			$table = $this->getTableName('fields');
			if (!\App\Db::getInstance()->isTableExists($table)) {
				return false;
			}
			$query = (new \App\Db\Query())->from($table)->where(['presence' => 0])->orderBy('sequence', SORT_ASC);
			if ($ids) {
				$query->andWhere(['id' => $ids]);
			}
			$fields = [];
			$dataReader = $query->createCommand()->query();
			while ($row = $dataReader->read()) {
				if ($viewType != 'Settings' && !$this->isActiveField($row)) {
					continue;
				}
				$inventoryFieldInstance = $this->getInventoryFieldInstance($row);
				if ($viewType == 'Detail' && !$inventoryFieldInstance->isVisible()) {
					continue;
				}
				if ($returnInBlock) {
					$fields[$row['block']][$row['columnname']] = $inventoryFieldInstance;
				} else {
					$fields[$row['columnname']] = $inventoryFieldInstance;
				}
			}
			$this->fields[$key] = $fields;
		} else {
			$fields = $this->fields[$key];
		}
		if ($returnInBlock) {
			if (!isset($fields[0])) {
				$fields[0] = [];
			}
			if (!isset($fields[1])) {
				$fields[1] = [];
			}
			if (!isset($fields[2])) {
				$fields[2] = [];
			}
		}
		\App\Log::trace('Exiting ' . __METHOD__);
		return $fields;
	}

	/**
	 * Check whether this field is active
	 * @param array $row Field entry from the database
	 * @return boolean
	 */
	public function isActiveField($row)
	{
		if (in_array($row['invtype'], ['Discount', 'DiscountMode'])) {
			$discountsConfig = \App\Modules\Base\Models\Inventory::getDiscountsConfig();
			if (empty($discountsConfig['active'])) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get inventory columns
	 * @param string $module Module name
	 * @param boolean $returnInBlock Should the result be divided into blocks
	 * @return array Inventory columns
	 */
	public function getColumns()
	{

		\App\Log::trace('Entering ' . __METHOD__ . '| ');
		if ($this->columns) {
			return $this->columns;
		}

		$columns = [];
		foreach ($this->getFields() as $key => $field) {
			$column = $field->getColumnName();
			if (!empty($column) && $column != '-')
				$columns[] = $column;
			foreach ($field->getCustomColumn() as $name => $field) {
				$columns[] = $name;
			}
		}
		$this->columns = $columns;
		\App\Log::trace('Exiting ' . __METHOD__);
		return $columns;
	}

	/**
	 * Creating installation of the field from the table
	 * @param string $valueArray Array of data
	 * @return \modelClassName Instance Vtiger_Basic_InventoryField
	 */
	public function getInventoryFieldInstance($valueArray)
	{

		\App\Log::trace('Entering ' . __METHOD__ . '| ');

		$className = \App\Loader::getComponentClassName('InventoryField', $valueArray['invtype'], $this->get('module'));
		$instance = new $className();
		$instance->initialize($valueArray);
		$instance->set('module', $this->get('module'));
		\App\Log::trace('Exiting ' . __METHOD__);
		return $instance;
	}

	/**
	 * Retrieve list of all fields
	 * @param string $moduleName Module name
	 * @return array Fields instance Vtiger_Basic_InventoryField
	 */
	public function getAllFields()
	{
		$moduleName = $this->get('module');

		\App\Log::trace('Entering ' . __METHOD__ . '| ' . $moduleName);

		$instance = \App\Cache\Cache::get('InventoryFields', $moduleName);
		if ($instance) {
			\App\Log::trace('Exiting ' . __METHOD__);
			return $instance;
		}

		$fieldPaths = ['src/Modules/Base/InventoryFields/'];
		if ($moduleName) {
			$fieldPaths[] = "src/Modules/$moduleName/InventoryFields/";
		} else {
			$moduleName = 'Base';
		}
		$fields = [];
		foreach ($fieldPaths as $fieldPath) {
			if (!is_dir($fieldPath))
				continue;
			foreach (new \DirectoryIterator($fieldPath) as $fileinfo) {
				if ($fileinfo->isFile() && $fileinfo->getFilename() != 'Basic.php') {
					$fieldName = str_replace('.php', '', $fileinfo->getFilename());
					$className = \App\Loader::getComponentClassName('InventoryField', $fieldName, $moduleName, false);
					if ($className === false) {
						continue;
					}
					$instance = new $className();
					$fields[$fieldName] = $instance->set('module', $moduleName);
				}
			}
		}
		\App\Cache\Cache::save('InventoryFields', $moduleName, $fields);
		\App\Log::trace('Exiting ' . __METHOD__);
		return $fields;
	}

	/**
	 * Retrieve list of parameters
	 * @param array $fields Array of instances fields (Vtiger_Basic_InventoryField)
	 * @return array Array of parameters
	 */
	public static function getMainParams($fields)
	{

		\App\Log::trace('Entering ' . __METHOD__);

		$params = false;
		if (isset($fields)) {
			foreach ($fields as $field) {
				if ($field->getName() == 'Name') {
					$params = \App\Json::decode($field->get('params'));
				}
			}
		}
		if (is_string($params['modules'])) {
			$params['modules'] = [$params['modules']];
		}
		\App\Log::trace('Exiting ' . __METHOD__);
		return $params;
	}

	/**
	 * Get \App\Modules\Base\Models\InventoryField instance
	 * @param string $moduleName Module name
	 * @return \modelClassName \App\Modules\Base\Models\InventoryField Instance
	 */
	public static function getInstance($moduleName)
	{
		$instance = \App\Cache\Cache::get('inventoryField', $moduleName);
		if (!$instance) {
			$modelClassName = \App\Loader::getComponentClassName('Model', 'InventoryField', $moduleName);
			$instance = new $modelClassName();
			$instance->set('module', $moduleName);
			\App\Cache\Cache::save('inventoryField', $moduleName, $instance);
		}
		return $instance;
	}

	/**
	 * Get \App\Modules\Base\Models\InventoryField instance
	 * @param string $moduleName Module name
	 * @return \modelClassName \App\Modules\Base\Models\InventoryField Instance
	 */
	public static function getFieldInstance($moduleName, $type)
	{
		$instance = \App\Cache\Cache::get('inventoryFieldType', $moduleName . $type);
		if (!$instance) {
			$inventoryClassName = \App\Loader::getComponentClassName('InventoryField', $type, $moduleName);
			$instance = new $inventoryClassName();
			$instance->set('module', $moduleName);
			\App\Cache\Cache::save('inventoryFieldType', $moduleName . $type, $instance);
		}
		return $instance;
	}

	/**
	 * Get fields to auto-complete
	 * @param string $moduleName
	 * @return array
	 */
	public function getAutoCompleteFieldsByModule($moduleName)
	{
		$fields = [];
		foreach ($this->getAutoCompleteFields() as $row) {
			if ($row['module'] == $moduleName) {
				$fields[] = $row;
			}
		}
		return $fields;
	}

	/**
	 * Get configuration parameters for taxes
	 * @param string $taxParam String parameters json encode
	 * @param int $net net price
	 * @param array $return
	 * @return array
	 */
	public static function getTaxParam($taxParam, $net, $return = false)
	{
		$taxParam = json_decode($taxParam, true);
		if (count($taxParam) == 0) {
			return [];
		}
		if (is_string($taxParam['aggregationType'])) {
			$taxParam['aggregationType'] = [$taxParam['aggregationType']];
		}
		if (!$return) {
			$return = [];
		}
		foreach ($taxParam['aggregationType'] as $aggregationType) {
			$precent = $taxParam[$aggregationType . 'Tax'];
			$return[$precent] += $net * ($precent / 100);
		}
		return $return;
	}

	/**
	 * Get related field name
	 * @param string $mainModule Module Name
	 * @return string
	 */
	public function getReferenceField($mainModule = 'Accounts')
	{
		$relationField = $this->get('relationField' . $mainModule);
		if (!$relationField) {
			$moduleModel = \App\Modules\Base\Models\Module::getInstance($this->get('module'));
			$modelFields = $moduleModel->getFields();
			$relationField = false;
			foreach ($modelFields as $fieldName => $fieldModel) {
				if ($fieldModel->isReferenceField()) {
					$referenceList = $fieldModel->getReferenceList();
					if (in_array($mainModule, $referenceList)) {
						$relationField = $fieldName;
						break;
					}
				}
			}
		}
		return $relationField;
	}

	/**
	 * Whether the module should be turned on Wysiwyg
	 * @param string $moduleName Module Name
	 * @return boolean|int
	 */
	public function isWysiwygType($moduleName)
	{
		if (!$moduleName) {
			return false;
		}
		$cache = \App\Cache\Cache::get('InventoryIsWysiwygType', $moduleName);
		if ($cache) {
			return $cache;
		}
		$return = 0;
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		$fieldModel = \App\Modules\Base\Models\Field::getInstance('description', $moduleModel);
		if ($fieldModel && $fieldModel->get('uitype') == '300') {
			$return = 1;
		}
		\App\Cache\Cache::save('InventoryIsWysiwygType', $moduleName, $return);
		return $return;
	}

	/**
	 * Get field name for the module taxes
	 * @param string $moduleName Module name
	 * @return string Tax field name
	 */
	public static function getTaxField($moduleName)
	{
		$cache = \App\Cache\Cache::get('InventoryIsGetTaxField', $moduleName);
		if ($cache) {
			return $cache;
		}
		$return = false;
		if ($moduleName === '') {
			return $return;
		}
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($moduleName);
		foreach ($moduleModel->getFields() as $fieldName => $fieldModel) {
			if ($fieldModel->get('uitype') == 303) {
				$return = $fieldName;
				continue;
			}
		}

		\App\Cache\Cache::save('InventoryIsGetTaxField', $moduleName, $return);
		return $return;
	}

	/**
	 * Get the value to save
	 * @param \App\Http\Vtiger_Request $request
	 * @param string $field Field name
	 * @param int $i Sequence number
	 * @return string
	 */
	public function getValueForSave($request, $field, $i)
	{
		$value = '';
		if ($request->has($field . $i)) {
			$value = $request->get($field . $i);
		} else if ($request->has($field)) {
			$value = $request->get($field);
		}

		if (in_array($field, $this->jsonFields) && $value != '') {
			$value = json_encode($value);
		}
		if (in_array($field, ['qty', 'price', 'gross', 'net', 'discount', 'purchase', 'margin', 'marginp', 'tax', 'total'])) {
			$value = CurrencyField::convertToDBFormat($value, null, true);
		}
		return $value;
	}

	/**
	 * Creating a new field
	 * @param string $type
	 * @param array $params
	 * @return array/false
	 */
	public function addField($type, $params)
	{
		$db = \App\Db::getInstance();
		$instance = self::getFieldInstance($this->get('module'), $type);

		$table = $this->getTableName();
		$columnName = $instance->getColumnName();
		$label = $instance->getDefaultLabel();
		$defaultValue = $instance->getDefaultValue();
		$colSpan = $instance->getColSpan();
		if (!$instance->isOnlyOne()) {
			$id = $this->getUniqueID($instance);
			$columnName = $columnName . $id;
		}
		if (isset($params['label'])) {
			$label = $params['label'];
		}
		if (isset($params['defaultValue'])) {
			$defaultValue = $params['defaultValue'];
		}
		if (isset($params['colSpan'])) {
			$colSpan = $params['colSpan'];
		}
		if (!isset($params['displayType'])) {
			$params['displayType'] = 0;
		}

		if ($instance->isColumnType()) {
			\vtlib\Utils::AddColumn($table, $columnName, $instance->getDBType());
			foreach ($instance->getCustomColumn() as $column => $criteria) {
				\vtlib\Utils::AddColumn($table, $column, $criteria);
			}
		}
		$tableName = $this->getTableName('fields');
		$db->createCommand()->insert($tableName, [
			'columnname' => $columnName,
			'label' => $label,
			'invtype' => $instance->getName(),
			'defaultvalue' => $defaultValue,
			'sequence' => $db->getUniqueID($tableName, 'sequence', false),
			'block' => $params['block'],
			'displaytype' => $params['displayType'],
			'params' => isset($params['params']) ? $params['params'] : '',
			'colspan' => $colSpan
		])->execute();
		return $db->getLastInsertID($tableName . '_id_seq');
	}

	/**
	 * Save field value
	 * @param array $param
	 * @return string/false
	 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
	 */
	public function saveField($type, $param)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$columns = ['label', 'invtype', 'defaultValue', 'sequence', 'block', 'displayType', 'params', 'colSpan'];
		$set = [];
		$params = [];
		foreach ($columns AS $columnName) {
			if (isset($param[$columnName])) {
				$set[strtolower($columnName)] = $param[$columnName];
			}
		}
		$id = $param['id'];
		$params[] = $id;
		if (!empty($set)) {
			$return = $db->update($this->getTableName('fields'), $set, '`id` = ?', [$id]);
		}
		return $return;
	}

	/**
	 * Save sequence field
	 * @param array $sequenceList
	 * @return string/false
	 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
	 */
	public function saveSequence($sequenceList)
	{
		$case = 'CASE id';
		foreach ($sequenceList as $sequence => $id) {
			$case .= ' WHEN ' . $id . ' THEN ' . $sequence;
		}
		$case .= ' END ';
		return \App\Db::getInstance()->createCommand()->update($this->getTableName('fields'), ['sequence' => new \yii\db\Expression($case)], ['id' => $sequenceList])->execute();
	}

	/**
	 * Delete inventory field
	 * @param array $param
	 * @return string/false
	 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
	 */
	public function delete($param = [])
	{
		$db = \App\Db::getInstance();
		$status = $db->createCommand()->delete($this->getTableName('fields'), ['id' => $param['id']])->execute();
		if ($status) {
			$fieldInstance = self::getFieldInstance($param['module'], $param['name']);
			$columns = array_keys($fieldInstance->getCustomColumn());
			$columns[] = $param['column'];
			foreach ($columns as $column) {
				$result = $db->createCommand()->dropColumn($this->getTableName('data'), $column)->execute();
			}
			return $result;
		}
		return false;
	}

	/**
	 * Getting unique id from invtype
	 * @return int
	 */
	public function getUniqueID($instance)
	{
		$adb = \App\Database\PearDatabase::getInstance();
		$query = sprintf('SELECT MAX(id) AS max FROM `%s` WHERE `invtype` = ? ', $this->getTableName('fields'));
		$result = $adb->pquery($query, [$instance->getName()]);
		return (int) $adb->getSingleValue($result) + 1;
	}

	/**
	 * Getting summary fields name
	 * @return array
	 */
	public function getSummaryFields()
	{
		$summaryFields = [];
		foreach ($this->getFields() as $field) {
			if ($field->isSummary()) {
				$summaryFields[$field->get('columnname')] = $field->get('columnname');
			}
		}
		return $summaryFields;
	}

	public function getAutoCompleteFields()
	{
		$instance = \App\Cache\Cache::get('AutoCompleteFields', $this->get('module'));
		if ($instance) {
			return $instance;
		}

		$db = \App\Database\PearDatabase::getInstance();
		$table = $this->getTableName('autofield');
		$result = $db->pquery(sprintf('SELECT * FROM %s', $table));
		$fields = [];
		while ($row = $db->getRow($result)) {
			$fields[$row['tofield']] = $row;
		}
		\App\Cache\Cache::save('AutoCompleteFields', $this->get('module'), $fields);
		return $fields;
	}

	public function getJsonFields()
	{
		return $this->jsonFields;
	}

	/**
	 * 
	 * @param \App\Modules\Base\Models\Record $recordModel
	 * @return float
	 */
	public function getInventoryPrice(\App\Modules\Base\Models\Record $recordModel)
	{
		return $recordModel->isEmpty('sum_total') ? 0 : $recordModel->get('sum_total');
	}

	/**
	 * Function to get list elements in iventory as html code
	 * @param \App\Modules\Base\Models\Record $recodModel
	 * @return string
	 */
	public function getInventoryListName(\App\Modules\Base\Models\Record $recodModel)
	{
		$inventoryFields = $this->getFields();
		$html = '<ul>';
		foreach ($recodModel->getInventoryData() as $data) {
			$html .= '<li>';
			$field = $inventoryFields['name'];
			$html .= $field->getDisplayValue($data['name']);
			$html .= '</li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Function to get custom values to complete in inventory
	 * @param string $sourceModuleName
	 * @param string $sourceFieldName
	 * @param \App\Modules\Base\Models\Record $recordModel
	 * @return array
	 */
	public function getCustomAutoComplete($sourceModuleName, $sourceFieldName, \App\Modules\Base\Models\Record $recordModel)
	{
		$inventoryMap = \App\AppConfig::module($sourceModuleName, 'INVENTORY_ON_SELECT_AUTO_COMPLETE');
		$values = [];
		if ($inventoryMap) {
			foreach ($inventoryMap as $fieldToComplete => $mapping) {
				if (isset($mapping[$sourceFieldName]) && method_exists($this, $mapping[$sourceFieldName])) {
					$methodName = $mapping[$sourceFieldName];
					$values[$fieldToComplete] = $this->$methodName($recordModel);
				}
			}
		}
		return $values;
	}
}
