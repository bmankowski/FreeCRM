<?php

namespace App\Modules\Base\UiTypes;

/**
 * Manual multi-record reference field (uitype 306).
 * Stores comma-separated CRM IDs in a TEXT column; target module from vtiger_fieldmodulerel.
 */
class MultiReference extends BaseUiType implements ReferenceListProvider
{

	public function isAjaxEditable()
	{
		return false;
	}

	public function isListviewSortable()
	{
		return false;
	}

	public function getTemplateName()
	{
		return 'uitypes/MultiReference.tpl';
	}

	public function getListSearchTemplateName()
	{
		if (\App\Core\AppConfig::performance('SEARCH_REFERENCE_BY_AJAX')) {
			return 'uitypes/MultiReferenceFieldSearchView.tpl';
		}
		return parent::getListSearchTemplateName();
	}

	/**
	 * @return string[]
	 */
	public function getReferenceList(): array
	{
		$fieldModel = $this->get('field');
		$fieldId = $fieldModel ? (int) $fieldModel->getId() : 0;
		if ($fieldId <= 0) {
			return [];
		}

		$list = [];
		foreach ((new \App\Db\Query())
			->select(['module' => 'relmodule'])
			->from('vtiger_fieldmodulerel')
			->innerJoin('vtiger_tab', 'vtiger_tab.name = vtiger_fieldmodulerel.relmodule')
			->where(['fieldid' => $fieldId])
			->andWhere(['<>', 'vtiger_tab.presence', 1])
			->orderBy(['sequence' => SORT_ASC])
			->column() as $moduleName) {
			if (\App\Security\Privilege::isPermitted($moduleName)) {
				$list[] = $moduleName;
			}
		}

		return $list;
	}

	/**
	 * @param mixed $value
	 * @return int[]
	 */
	public static function parseIds($value): array
	{
		if ($value === null || $value === '' || $value === []) {
			return [];
		}
		if (is_array($value)) {
			$parts = $value;
		} else {
			$parts = explode(',', (string) $value);
		}
		$ids = [];
		foreach ($parts as $part) {
			$id = (int) trim((string) $part);
			if ($id > 0) {
				$ids[$id] = $id;
			}
		}
		return array_values($ids);
	}

	public function getReferenceModule($value)
	{
		$fieldModel = $this->get('field');
		$referenceModuleList = $fieldModel->getReferenceList();
		$referenceEntityType = \App\Records\Record::getType($value);
		if (!empty($referenceModuleList) && in_array($referenceEntityType, $referenceModuleList, true)) {
			return \App\Modules\Base\Models\Module::getInstance($referenceEntityType);
		}
		if (!empty($referenceModuleList) && in_array('Users', $referenceModuleList, true)) {
			return \App\Modules\Base\Models\Module::getInstance('Users');
		}
		return null;
	}

	/**
	 * @param mixed $value
	 * @return array<int, array{id: int, label: string, module: string}>
	 */
	public function getEditViewDisplayValue($value, $record = false)
	{
		$records = [];
		foreach (self::parseIds($value) as $id) {
			$referenceModule = $this->getReferenceModule($id);
			if (!$referenceModule) {
				continue;
			}
			$referenceModuleName = $referenceModule->get('name');
			if ($referenceModuleName === 'Users' || $referenceModuleName === 'Groups') {
				$name = \App\Fields\Owner::getLabel($id);
			} else {
				$name = \App\Records\Record::getLabel($id);
			}
			if ($name === '') {
				continue;
			}
			$records[] = [
				'id' => $id,
				'label' => $name,
				'module' => $referenceModuleName,
			];
		}
		return $records;
	}

	public function getDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		return $this->formatLinkedLabels($value, $rawText, \App\Core\AppConfig::main('href_max_length'));
	}

	public function getListViewDisplayValue($value, $record = false, $recordInstance = false, $rawText = false)
	{
		$maxLength = $this->get('field')->get('maxlengthtext');
		return $this->formatLinkedLabels($value, $rawText, $maxLength);
	}

	/**
	 * @param mixed $value
	 */
	private function formatLinkedLabels($value, bool $rawText, $maxLength): string
	{
		$parts = [];
		foreach (self::parseIds($value) as $id) {
			$referenceModule = $this->getReferenceModule($id);
			if (!$referenceModule) {
				continue;
			}
			$referenceModuleName = $referenceModule->get('name');
			if ($referenceModuleName === 'Users' || $referenceModuleName === 'Groups') {
				$name = \App\Fields\Owner::getLabel($id);
			} else {
				$name = \App\Records\Record::getLabel($id);
			}
			if ($name === '') {
				continue;
			}
			if ($rawText || $referenceModuleName === 'Users' || !\App\Security\Privilege::isPermitted($referenceModuleName, 'DetailView', $id)) {
				$parts[] = $name;
				continue;
			}
			$name = \vtlib\Functions::textLength($name, $maxLength);
			$parts[] = "<a class='moduleColor_$referenceModuleName' href='index.php?module=$referenceModuleName&view="
				. $referenceModule->getDetailViewName() . "&record=$id' title='"
				. \App\Runtime\Vtiger_Language_Handler::translate($referenceModuleName, $referenceModuleName)
				. "'>$name</a>";
		}
		return implode(', ', $parts);
	}

	public function getDBValue($value, $recordModel = false)
	{
		$fieldModel = $this->get('field');
		$allowedModules = $fieldModel->getReferenceList();
		$valid = [];
		foreach (self::parseIds($value) as $id) {
			$type = \App\Records\Record::getType($id);
			if ($type && in_array($type, $allowedModules, true)) {
				$valid[] = $id;
			}
		}
		return implode(',', $valid);
	}
}
