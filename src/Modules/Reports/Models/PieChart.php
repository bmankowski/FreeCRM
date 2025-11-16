<?php

namespace App\Modules\Reports\Models;

class PieChart extends Base_Chart
{

	public function generateData()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$values = [];
		$chartSQL = $this->getQuery();
		$result = $db->pquery($chartSQL, array());
		$rows = $db->num_rows($result);

		$queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();
		if (is_array($queryColumnsByFieldModel)) {
			foreach ($queryColumnsByFieldModel as $field) {
				$sector = $field->get('reportlabel');
				$sectorField = $field;
			}
		}

		if ($this->isRecordCount()) {
			$sector = 'RECORD_COUNT';
		}

		$groupByColumnsByFieldModel = $this->getGroupbyColumnsByFieldModel();

		if (is_array($groupByColumnsByFieldModel)) {
			foreach ($groupByColumnsByFieldModel as $groupField) {
				$legend = $groupByColumns[] = $groupField->get('reportlabel');
				$legendField = $groupField;
			}
		}

		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$currencyRateAndSymbol = \vtlib\Functions:: getCurrencySymbolandRate($currentUserModel->currency_id);

		for ($i = 0; $i < $rows; $i++) {
			$row = $db->query_result_rowdata($result, $i);
			$value = (float) $row[$sector];
			if (!$this->isRecordCount()) {
				if ($sectorField) {
					if ($sectorField->get('uitype') != '7') {
						if ($sectorField->get('uitype') == '71' || $sectorField->get('uitype') == '72') { //convert currency fields
							$value = \App\Fields\CurrencyField::convertFromDollar($value, $currencyRateAndSymbol['rate']);
						} else {
							$value = (int) $sectorField->getDisplayValue($row[$sector]);
						}
					}
				}
			}
			$values[] = $value;
			//translate picklist and multiselect picklist values
			if ($legendField) {
				$fieldDataType = $legendField->getFieldDataType();
				if ($fieldDataType == 'picklist') {
					$label = \App\Runtime\Vtiger_Language_Handler::translate($row[$legend], $legendField->getModuleName());
				} else if ($fieldDataType == 'multipicklist') {
					$multiPicklistValue = $row[$legend];
					$multiPicklistValues = explode(' |##| ', $multiPicklistValue);
					foreach ($multiPicklistValues as $multiPicklistValue) {
						$labelList[] = \App\Runtime\Vtiger_Language_Handler::translate($multiPicklistValue, $legendField->getModuleName());
					}
					$label = implode(',', $labelList);
				} else if ($fieldDataType == 'date') {
					$label = \App\Modules\Base\UiTypes\Date::getDisplayDateValue($row[strtolower($legendField->get('reportlabel'))]);
				} else if ($fieldDataType == 'datetime') {
					$label = \App\Modules\Base\UiTypes\Date::getDisplayDateTimeValue($row[strtolower($legendField->get('reportlabel'))]);
				} else {
					$label = $row[$legend];
				}
			} else {
				$label = $row[$legend];
			}
			$labels[] = (strlen($label) > 30) ? substr($label, 0, 30) . '..' : $label;
			$links[] = $this->generateLink($legendField->get('reportcolumninfo'), $row[strtolower($legend)]);
		}

		$data = array('labels' => $labels,
			'values' => $values,
			'links' => $links,
			'graph_label' => $this->getGraphLabel()
		);
		return $data;
	}
}