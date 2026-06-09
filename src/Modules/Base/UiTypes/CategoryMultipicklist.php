<?php

namespace App\Modules\Base\UiTypes;

/**
 * UIType Category multipicklist
 * @package YetiForce.UIType
 * @license licenses/License.html
 * @author Krzysztof Gastołek <krzysztof.gastolek@wars.pl>
 * @author Tomasz Kur <t.kur@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class CategoryMultipicklist extends BaseUiType
{

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param string $tree
	 * @param int $record
	 * @param \App\Modules\Base\Models\Record $recordInstance
	 * @param boolean $rawText
	 * @return string
	 */
	public function getDisplayValue($tree, $record = false, $recordInstance = false, $rawText = false)
	{
		$fieldModel = $this->get('field');
		$template = $fieldModel->getFieldParams();
		$module = $fieldModel->getModuleName();

		if (empty($tree)) {
			return '';
		}
		$names = [];
		$trees = array_filter(explode(',', $tree));
		if (\App\Cache\Cache::has('TreeData', $template)) {
			$treeData = \App\Cache\Cache::get('TreeData', $template);
		} else {
			$treeData = (new \App\Db\Query())
				->select(['tree', 'name', 'parenttrre', 'depth', 'label'])
				->from('vtiger_trees_templates_data')
				->where(['templateid' => $template])
				->createCommand()
				->queryAllByGroup(1);
			\App\Cache\Cache::save('TreeData', $template, $treeData, \App\Cache\Cache::LONG);
		}

		foreach ($trees as $treeId) {
			if (isset($treeData[$treeId])) {
				$row = $treeData[$treeId];
				if ($row['depth'] > 0) {
					$parenttrre = $row['parenttrre'];
					$pieces = explode('::', $parenttrre);
					end($pieces);
					$parent = prev($pieces);
					$parentName = isset($treeData[$parent]) ? $treeData[$parent]['label'] : '';
					$parentName = '(' . \App\Runtime\Vtiger_Language_Handler::translate($parentName, $module) . ') ';
					$names[] = $parentName . \App\Runtime\Vtiger_Language_Handler::translate($row['label'], $module);
				} else {
					$names[] = \App\Runtime\Vtiger_Language_Handler::translate($row['label'], $module);
				}
			}
		}
		return implode(', ', $names);
	}

	public function getAllValue()
	{
		$tree = new Tree();
		$tree->set('field', $this->get('field'));
		return $tree->getAllValue();
	}

	/**
	 * Function to get the DB Insert Value, for the current field type with given User Value
	 * @param mixed $value
	 * @param \App\Modules\Base\Models\Record $recordModel
	 * @return string
	 */
	public function getDBValue($value, $recordModel = false)
	{
		if ($value) {
			$value = ",$value,";
		} elseif (is_null($value)) {
			$value = '';
		}
		return $value;
	}
}
