<?php

namespace App\Modules\Settings\LayoutEditor\Models;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * ********************************************************************************** */

class Field extends \App\Modules\Base\Models\Field
{

	/**
	 * Function to remove field
	 */
	public function delete()
	{
		$db = \App\Db\Db::getInstance();
		$fieldId = $this->getId();
		$moduleId = $this->getModuleId();
		$fieldName = $this->getName();

		$fieldService = \App\ModuleManagement\ServiceLocator::getFieldService();
		$fieldService->delete($fieldId);

		\App\Cache\Cache::delete('ModuleFields', $moduleId);
		\App\Cache\Cache::delete('fieldInfo', $moduleId);
		\App\Cache\Cache::delete('field-' . $moduleId, $fieldId);
		\App\Cache\Cache::delete('field-' . $moduleId, $fieldName);
		if (isset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[$moduleId][$fieldName])) {
			unset(\App\Utils\VTCacheUtils::$_fieldinfo_cache[$moduleId][$fieldName]);
		}

		$fldModule = $this->getModuleName();
		$uitype = $this->get('uitype');
		$typeofdata = $this->get('typeofdata');
		$oldfieldlabel = $this->get('label');
		$tablename = $this->get('table');
		$columnName = $this->get('column');
		$tabId = $moduleId;

		$focus = \App\Core\CRMEntity::getInstance($fldModule);

		$deleteColumnName = $tablename . ":" . $columnName . ":" . $fieldName . ":" . $fldModule . "_" . str_replace(" ", "_", $oldfieldlabel) . ":" . $typeofdata;
		$columnCvstdfilter = $tablename . ":" . $columnName . ":" . $fieldName . ":" . $fldModule . "_" . str_replace(" ", "_", $oldfieldlabel);
		$selectColumnname = $tablename . ":" . $columnName . ":" . $fldModule . "_" . str_replace(" ", "_", $oldfieldlabel) . ":" . $fieldName . ":" . $typeofdata;
		$reportsummaryColumn = $tablename . ":" . $columnName . ":" . str_replace(" ", "_", $oldfieldlabel);
		if ($tablename != 'vtiger_crmentity' && $db->isTableExists($tablename) && $db->getSchema()->getTableSchema($tablename, true)->getColumn($columnName)) {
			$db->createCommand()->dropColumn($tablename, $columnName)->execute();
		}
		//we have to remove the entries in customview and report related tables which have this field ($colName)
		$db->createCommand()->delete('vtiger_cvcolumnlist', ['columnname' => $deleteColumnName])->execute();
		$db->createCommand()->delete('vtiger_cvstdfilter', ['columnname' => $columnCvstdfilter])->execute();
		$db->createCommand()->delete('vtiger_cvadvfilter', ['columnname' => $deleteColumnName])->execute();
		$db->createCommand()->delete('vtiger_selectcolumn', ['columnname' => $selectColumnname])->execute();
		$db->createCommand()->delete('vtiger_relcriteria', ['columnname' => $selectColumnname])->execute();
		$db->createCommand()->delete('vtiger_reportsortcol', ['columnname' => $selectColumnname])->execute();
		$db->createCommand()->delete('vtiger_reportdatefilter', ['datecolumnname' => $columnCvstdfilter])->execute();
		$db->createCommand()->delete('vtiger_reportsummary', ['like', 'columnname', $reportsummaryColumn])->execute();
		//Deleting from convert lead mapping vtiger_table- Jaguar
		if ($fldModule == 'Leads') {
			$db->createCommand()->delete('vtiger_convertleadmapping', ['leadfid' => $fieldId])->execute();
		} elseif ($fldModule == 'Accounts') {
			$mapDelId = ['Accounts' => 'accountfid'];
			$db->createCommand()->update('vtiger_convertleadmapping', [$mapDelId[$fldModule] => 0], [$mapDelId[$fldModule] => $fieldId])->execute();
		}

		//HANDLE HERE - we have to remove the table for other picklist type values which are text area and multiselect combo box
		if ($this->getFieldDataType() == 'picklist' || $this->getFieldDataType() == 'multipicklist') {
			$query = (new \App\Db\Query())->from('vtiger_field')
				->where(['columnname' => $columnName])
				->andWhere(['in', 'uitype', [15, 16, 33]]);
			$dataReader = $query->createCommand()->query();
			if (!$dataReader->count()) {
				$db->createCommand()->dropTable('vtiger_' . $columnName)->execute();
				//To Delete Sequence Table 
				if ($db->isTableExists('vtiger_' . $columnName . '_seq')) {
					$db->createCommand()->dropTable('vtiger_' . $columnName . '_seq')->execute();
				}
				$db->createCommand()->delete('vtiger_picklist', ['name' => $columnName]);
			}
			$db->createCommand()->delete('vtiger_picklist_dependency', ['and', "tabid = $tabId", ['or', "sourcefield = '$columnname'", "targetfield = '$columnname'"]])->execute();
		}
	}

	/**
	 * Function to Move the field
	 * @param <Array> $fieldNewDetails
	 * @param <Array> $fieldOlderDetails
	 */
	public function move($fieldNewDetails, $fieldOlderDetails)
	{
		$db = \App\Db\Db::getInstance();
		$newBlockId = $fieldNewDetails['blockId'];
		$olderBlockId = $fieldOlderDetails['blockId'];

		$newSequence = $fieldNewDetails['sequence'];
		$olderSequence = $fieldOlderDetails['sequence'];

		if ($olderBlockId == $newBlockId) {
			if ($newSequence > $olderSequence) {
				$db->createCommand()->update('vtiger_field', ['sequence' => new \yii\db\Expression('sequence - 1')], ['and', 'sequence > :olderSequence', 'sequence <= :newSequence', 'block = :olderBlockId'], [':olderSequence' => $olderSequence, ':newSequence' => $newSequence, ':olderBlockId' => $olderBlockId])->execute();
			} else if ($newSequence < $olderSequence) {
				$db->createCommand()->update('vtiger_field', ['sequence' => new \yii\db\Expression('sequence + 1')], ['and', 'sequence < :olderSequence', 'sequence >= :newSequence', 'block = :olderBlockId'], [':olderSequence' => $olderSequence, ':newSequence' => $newSequence, ':olderBlockId' => $olderBlockId])->execute();
			}
			$db->createCommand()->update('vtiger_field', ['sequence' => $newSequence], ['fieldid' => $this->getId()])->execute();
		} else {
			$db->createCommand()->update('vtiger_field', ['sequence' => new \yii\db\Expression('sequence - 1')], ['and', 'sequence > :olderSequence', 'block = :olderBlockId'], [':olderSequence' => $olderSequence, ':olderBlockId' => $olderBlockId])->execute();
			$db->createCommand()->update('vtiger_field', ['sequence' => new \yii\db\Expression('sequence - 1')], ['and', 'sequence >= :newSequence', 'block = :newBlockId'], [':newSequence' => $newSequence, ':newBlockId' => $newBlockId])->execute();

			$db->createCommand()->update('vtiger_field', ['sequence' => $newSequence, 'block' => $newBlockId], ['fieldid' => $this->getId()])->execute();
		}
	}

	public static function makeFieldActive($fieldIdsList = array(), $blockId)
	{
		$maxSequence = (new \App\Db\Query())->from('vtiger_field')->where(['block' => $blockId, 'presence' => [0, 2]])->max('sequence');

		$caseExpression = 'CASE';
		foreach ($fieldIdsList as $fieldId) {
			$caseExpression .= " WHEN fieldid = $fieldId THEN " . ($maxSequence + 1);
		}
		$caseExpression .= ' ELSE sequence END';
		\App\Db\Db::getInstance()->createCommand()
			->update('vtiger_field', [
				'presence' => 2,
				'sequence' => new \yii\db\Expression($caseExpression),
				], ['fieldid' => $fieldIdsList])->execute();
	}

	/**
	 * Function which specifies whether the field can have mandatory switch to happen
	 * @return boolean - true if we can make a field mandatory and non mandatory , false if we cant change previous state
	 */
	public function isMandatoryOptionDisabled()
	{
		$moduleModel = $this->getModule();
		if (!$moduleModel || !method_exists($moduleModel, 'getCumplosoryMandatoryFieldList')) {
			return false;
		}
		$complusoryMandatoryFieldList = $moduleModel->getCumplosoryMandatoryFieldList();
		//uitypes for which mandatory switch is disabled
		$mandatoryRestrictedUitypes = array('4', '70');
		if (in_array($this->getName(), $complusoryMandatoryFieldList)) {
			return true;
		}
		if (in_array($this->get('uitype'), $mandatoryRestrictedUitypes) || ($this->get('displaytype') == 2 && $this->get('uitype') != 306)) {
			return true;
		}
		return false;
	}

	/**
	 * Function which will specify whether the active option is disabled
	 * @return boolean
	 */
	public function isActiveOptionDisabled()
	{
		if ($this->get('presence') == 0 || $this->get('uitype') == 306 || $this->isMandatoryOptionDisabled()) {
			return true;
		}
		return false;
	}

	/**
	 * Function which will specify whether the quickcreate option is disabled
	 * @return boolean
	 */
	public function isQuickCreateOptionDisabled()
	{
		$moduleModel = $this->getModule();
		if (!$moduleModel || !method_exists($moduleModel, 'isQuickCreateSupported')) {
			return true;
		}
		if ($this->get('quickcreate') == 0 || $this->get('quickcreate') == 3 || !$moduleModel->isQuickCreateSupported() || $this->get('uitype') == 69) {
			return true;
		}
		return false;
	}

	/**
	 * Function which will specify whether the mass edit option is disabled
	 * @return boolean
	 */
	public function isMassEditOptionDisabled()
	{
		if ($this->get('masseditable') == 0 || $this->get('displaytype') != 1 || $this->get('masseditable') == 3 || $this->get('uitype') == 69) {
			return true;
		}
		return false;
	}

	/**
	 * Function which will specify whether the default value option is disabled
	 * @return boolean
	 */
	public function isDefaultValueOptionDisabled()
	{
		if ($this->isMandatoryOptionDisabled() || $this->isReferenceField() || $this->get('uitype') == 69) {
			return true;
		}
		return false;
	}

	/**
	 * Function to check whether summary field option is disable or not
	 * @return boolean true/false
	 */
	public function isSummaryFieldOptionDisabled()
	{
		return $this->get('uitype') === 70;
	}

	/**
	 * Function to check field is editable or not
	 * @return boolean true/false
	 */
	public function isEditable()
	{
		$moduleModel = $this->module ?? null;
		if (!$moduleModel) {
			$moduleModel = $this->getModule();
		}

		$moduleName = null;
		if ($moduleModel instanceof \App\Modules\Base\Models\Module) {
			$moduleName = $moduleModel->getName();
		} elseif ($moduleModel instanceof \vtlib\Module) {
			$moduleName = $moduleModel->name;
		} elseif (is_string($moduleModel)) {
			$moduleName = $moduleModel;
		}

		if (!$moduleName && $this->getModuleId()) {
			$moduleName = \App\Utils\ModuleUtils::getModuleName($this->getModuleId());
		}

		if ($moduleName && in_array($moduleName, ['Calendar', 'Events'], true)) {
			return false;
		}
		return true;
	}

	/**
	 * Function to get instance
	 * @param string $value - fieldname or fieldid
	 * @param <type> $module - optional - module instance
	 * @return \App\Modules\Settings\LayoutEditor\Models\Field
	 */
	public static function getInstance($value, $module = false)
	{
		$fieldObject = parent::getInstance($value, $module);
		$objectProperties = get_object_vars($fieldObject);
		$fieldModel = new self();
		foreach ($objectProperties as $properName => $propertyValue) {
			$fieldModel->$properName = $propertyValue;
		}
		return $fieldModel;
	}

	/**
	 * Function to get all fields list for all blocks
	 * @param array List of block ids
	 * @param \App\Modules\Base\Models\Module $moduleInstance
	 * @return array<\App\Modules\Settings\LayoutEditor\Models\Field> List of Field models 
	 */
	public static function getInstanceFromBlockIdList($blockId, $moduleInstance = false)
	{
		if (!is_array($blockId)) {
			$blockId = [$blockId];
		}
		$query = (new \App\Db\Query())->from('vtiger_field')->where(['block' => $blockId, 'displaytype' => [1, 2, 4, 5, 9, 10]])->orderBy('sequence');
		$dataReader = $query->createCommand()->query();
		$fieldModelsList = [];
		while ($row = $dataReader->read()) {
			$fieldModel = new self();
			$fieldModel->initialize($row);
			if ($moduleInstance) {
				$fieldModel->setModule($moduleInstance);
			}
			$fieldModelsList[] = $fieldModel;
		}
		return $fieldModelsList;
	}

	/**
	 * Function to get the field details
	 * @param array $context
	 * @return <Array> - array of field values
	 */
	public function getFieldInfo($context = [])
	{
		$fieldInfo = parent::getFieldInfo($context);
		$fieldInfo['isQuickCreateDisabled'] = $this->isQuickCreateOptionDisabled();
		$fieldInfo['isSummaryField'] = $this->isSummaryField();
		$fieldInfo['isSummaryFieldDisabled'] = $this->isSummaryFieldOptionDisabled();
		$fieldInfo['isMassEditDisabled'] = $this->isMassEditOptionDisabled();
		$fieldInfo['isDefaultValueDisabled'] = $this->isDefaultValueOptionDisabled();
		return $fieldInfo;
	}
}
