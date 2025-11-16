<?php

namespace App\Modules\Base\UiTypes;

/**
 * UIType MultiReferenceValue Field Class
 * @package YetiForce.Fields
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class MultiReferenceValue extends BaseUiType
{

	const COMMA = '|#|';

	/**
	 * Function to get the Template name for the current UI Type object
	 * @return string - Template Name
	 */
	public function getTemplateName()
	{
		return 'uitypes/MultiReferenceValue.tpl';
	}

	public function getListSearchTemplateName()
	{
		return 'uitypes/MultiReferenceValueFieldSearchView.tpl';
	}

	/**
	 * Function to get all the available picklist values for the current field
	 * @return <Array> List of picklist values if the field is of type MultiReferenceValue.
	 */
	public function getPicklistValues()
	{
		$picklistValues = $this->get('picklistValues');
		if (!empty($picklistValues)) {
			return $picklistValues;
		}
		$params = $this->get('field')->getFieldParams();
		$fieldInfo = \App\Fields\Field::getFieldInfo($params['field']);
		$queryGenerator = new \App\QueryGenerator($params['module']);
		if ($params['filterField'] !== '-') {
			$queryGenerator->addCondition($params['filterField'], $params['filterValue'], 'e');
		}
		$queryGenerator->setFields([$fieldInfo['fieldname']]);

		$values = $queryGenerator->createQuery()->distinct()->indexBy($fieldInfo['column'])->column();
		$this->set('picklistValues', $values);
		return $values;
	}

	/**
	 * Loading the list of multireference fields
	 * @param string $sourceModule Source module name
	 * @param string $destinationModule Destination module name
	 * @return array
	 */
	public static function getFieldsByModules($sourceModule, $destinationModule)
	{
		$cacheKey = "$sourceModule,$destinationModule";
		if (\App\Cache\Cache::has('mrvfbm', $cacheKey)) {
			return \App\Cache\Cache::get('mrvfbm', $cacheKey);
		}
		$fields = (new \App\Db\Query())
				->from('vtiger_field')
				->where(['tabid' => \App\Utils\ModuleUtils::getModuleId($sourceModule), 'uitype' => 305])
				->andWhere(['<>', 'presence', 1])
				->andWhere(['like', 'fieldparams', '{"module":"' . $destinationModule . '"%', false])->all();
		\App\Cache\Cache::get('mrvfbm', $cacheKey, $fields, \App\Cache\Cache::LONG);
		return $fields;
	}

	/**
	 * Get MultiReference modules
	 * @param string $moduelName
	 * @return array
	 */
	public static function getMultiReferenceModules($moduelName)
	{
		if (\App\Cache\Cache::has('getMultiReferenceModules', $moduelName)) {
			return \App\Cache\Cache::get('getMultiReferenceModules', $moduelName);
		}
		$moduleIds = (new \App\Db\Query())->select(['tabid'])->from('vtiger_field')->where(['uitype' => 305])->andWhere(['<>', 'presence', 1])
				->andWhere(['like', 'fieldparams', '{"module":"' . $moduelName . '"%', false])->distinct()->column();
		\App\Cache\Cache::get('getMultiReferenceModules', $moduelName, $moduleIds, \App\Cache\Cache::LONG);
		return $moduleIds;
	}

	/**
	 * Set record to cron
	 * @param string $moduleName
	 * @param string $destModule
	 * @param int $recordId
	 * @param int $type
	 */
	public static function setRecordToCron($moduleName, $destModule, $recordId, $type = 1)
	{
		\App\Db::getInstance()->createCommand()->insert('s_#__multireference', ['source_module' => $moduleName, 'dest_module' => $destModule, 'lastid' => $recordId, 'type' => $type])->execute();
	}

	/**
	 * Getting the value for multireference
	 * @param \App\CRMEntity $entity \App\CRMEntity instance
	 * @param int $sourceRecord
	 * @param int $destRecord
	 * @return array
	 */
	public function getRecordValues(CRMEntity $entity, $sourceRecord, $destRecord)
	{
		$db = \App\Database\PearDatabase::getInstance();
		$params = $this->get('field')->getFieldParams();
		$fieldModel = $this->get('field');
		// Get current value
		$currentValue = \vtlib\Functions:: getSingleFieldValue($fieldModel->getTableName(), $fieldModel->getColumnName(), $entity->tab_name_index[$fieldModel->getTableName()], $sourceRecord);
		// Get value to added
		$relatedValue = '';
		$fieldInfo = \App\Fields\Field::getFieldInfo($params['field']);
		$recordModel = \App\Modules\Base\Models\Record::getInstanceById($destRecord, $params['module']);
		if ($params['filterField'] === '-' || ($params['filterField'] !== '-' && $recordModel->get($params['filterField']) === $params['filterValue'])) {
			$relatedValue = $recordModel->get($fieldInfo['fieldname']);
		}
		return ['currentValue' => $currentValue, 'relatedValue' => $relatedValue];
	}

	/**
	 * Add value to multireference
	 * @param \App\CRMEntity $entity \App\CRMEntity instance
	 * @param int $sourceRecord 
	 * @param int $destRecord
	 */
	public function addValue(CRMEntity $entity, $sourceRecord, $destRecord)
	{
		$values = $this->getRecordValues($entity, $sourceRecord, $destRecord);
		$currentValue = $values['currentValue'];
		if (strpos($currentValue, self::COMMA . $values['relatedValue'] . self::COMMA) !== false || empty($values['relatedValue'])) {
			return;
		}
		if (empty($currentValue)) {
			$currentValue = self::COMMA;
		}
		$currentValue .= $values['relatedValue'] . self::COMMA;
		\App\Db::getInstance()->createCommand()->update($this->get('field')->get('table'), [
			$this->get('field')->get('column') => $currentValue
			], [$entity->tab_name_index[$this->get('field')->get('table')] => $sourceRecord]
		)->execute();
	}

	/**
	 * Update the value for relation
	 * @param string $sourceModule Source module name
	 * @param int $sourceRecord Source record
	 */
	public function reloadValue($sourceModule, $sourceRecord)
	{
		$field = $this->get('field');
		$params = $field->getFieldParams();
		$sourceRecordModel = \App\Modules\Base\Models\Record::getInstanceById($sourceRecord, $sourceModule);

		$targetModel = \App\Modules\Base\Models\RelationListView::getInstance($sourceRecordModel, $params['module']);
		$fieldInfo = \App\Fields\Field::getFieldInfo($params['field']);
		$targetModel->getRelationQuery();
		$queryGenerator = $targetModel->getRelationModel()->getQueryGenerator();
		$queryGenerator->permissions = false;
		if ($params['filterField'] !== '-') {
			$queryGenerator->addCondition($params['filterField'], $params['filterValue'], 'e');
		}
		$queryGenerator->setFields([$fieldInfo['fieldname']]);
		$query = $queryGenerator->createQuery(true);
		$values = $query->distinct()->indexBy($fieldInfo['column'])->column();
		if ($values) {
			$values = self::COMMA . implode(self::COMMA, $values) . self::COMMA;
		}
		\App\Db::getInstance()->createCommand()->update($field->get('table'), [
			$field->get('column') => $values
			], [$sourceRecordModel->getEntity()->tab_name_index[$field->get('table')] => $sourceRecord]
		)->execute();
	}

	/**
	 * Function to get all the available picklist values for the current field
	 * @return <Array> List of picklist values if the field is of type MultiReferenceValue.
	 */
	public function getPicklistValuesForModuleList($module, $view)
	{
		$queryGenerator = new \App\QueryGenerator($module);
		$queryGenerator->initForCustomViewById($view);
		$queryGenerator->setFields([$this->get('field')->get('name')]);
		$query = $queryGenerator->createQuery();
		$dataReader = $query->distinct()->createCommand()->query();
		$values = [];
		while (($value = $dataReader->readColumn(0)) !== false) {
			$value = explode(self::COMMA, trim($value, self::COMMA));
			$values = array_merge($values, $value);
		}

		return array_unique($values);
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $value
	 * @param integer $record
	 * @param \App\Modules\Base\Models\Record $recordInstance
	 * @param string $rawText
	 * @return string
	 */
	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$value = str_replace(self::COMMA, ', ', $value);
		$value = substr($value, 1);
		$value = substr($value, 0, -2);

		return $value;
	}

	/**
	 * Function to get the Display Value in ListView
	 * @param string $value
	 * @param int $record
	 * @param \App\Modules\Base\Models\Record $recordInstance
	 * @param bool $rawText
	 * @return string
	 */
	public function getListViewDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$field = $this->get('field');
		$params = $field->getFieldParams();
		$fieldInfo = \App\Fields\Field::getFieldInfo($params['field']);
		if (in_array($fieldInfo['uitype'], [15, 16, 33])) {
			$relModuleName = \App\Utils\ModuleUtils::getModuleName($fieldInfo['tabid']);
			$values = array_filter(explode(self::COMMA, $value));
			foreach ($values as &$value) {
				$value = \App\Runtime\Vtiger_Language_Handler::translate($value, $relModuleName);
			}
			$values = implode(', ', $values);
		} else {
			$values = $this->getDisplayValue($value, $record, $recordInstance, $rawText);
		}
		return \vtlib\Functions:: textLength($values, $field->get('maxlengthtext'));
	}
}
