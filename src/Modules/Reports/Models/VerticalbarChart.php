<?php

namespace App\Modules\Reports\Models;

class VerticalbarChart extends Base_Chart
{

	public function generateData()
	{
		$db = \App\Database\PearDatabase::getInstance();
		$chartSQL = $this->getQuery();

		$result = $db->pquery($chartSQL, array());
		$rows = $db->num_rows($result);
		$values = array();

		$queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();

		$recordCountLabel = '';
		if ($this->isRecordCount()) {
			$recordCountLabel = 'RECORD_COUNT';
		}

		$groupByColumnsByFieldModel = $this->getGroupbyColumnsByFieldModel();

		$currentUserModel = \App\Modules\Users\Models\Record::getCurrentUserModel();
		$currencyRateAndSymbol = \vtlib\Functions:: getCurrencySymbolandRate($currentUserModel->currency_id);
		$links = array();

		for ($i = 0; $i < $rows; $i++) {
			$row = $db->query_result_rowdata($result, $i);

			if ($recordCountLabel) {
				$values[$i][] = (int) $row[$recordCountLabel];
			}

			if ($queryColumnsByFieldModel) {
				foreach ($queryColumnsByFieldModel as $fieldModel) {
					if ($fieldModel->get('uitype') == '71' || $fieldModel->get('uitype') == '72') {
						$value = (float) ($row[$fieldModel->get('reportlabel')]);
						$values[$i][] = \App\Fields\CurrencyField::convertFromDollar($value, $currencyRateAndSymbol['rate']);
					} else {
						$values[$i][] = (float) $row[$fieldModel->get('reportlabel')];
					}
				}
			}

			if ($groupByColumnsByFieldModel) {
				foreach ($groupByColumnsByFieldModel as $gFieldModel) {
					$fieldDataType = $gFieldModel->getFieldDataType();
					if ($fieldDataType == 'picklist') {
						$label = \App\Runtime\Vtiger_Language_Handler::translate($row[$gFieldModel->get('reportlabel')], $gFieldModel->getModuleName());
					} else if ($fieldDataType == 'multipicklist') {
						$multiPicklistValue = $row[$gFieldModel->get('reportlabel')];
						$multiPicklistValues = explode(' |##| ', $multiPicklistValue);
						foreach ($multiPicklistValues as $multiPicklistValue) {
							$labelList[] = \App\Runtime\Vtiger_Language_Handler::translate($multiPicklistValue, $gFieldModel->getModuleName());
						}
						$label = implode(',', $labelList);
					} else if ($fieldDataType == 'date') {
						$label = \App\Modules\Base\UiTypes\Date::getDisplayDateValue($row[$gFieldModel->get('reportlabel')]);
					} else if ($fieldDataType == 'datetime') {
						$label = $row[$gFieldModel->get('reportlabel')];
						$columnInfo = explode(':', $gFieldModel->get('reportcolumninfo'));
						if (isset($columnInfo[5]) && $columnInfo[5] === 'MY') {
							$m = explode(' ', $label);
							$label = \App\Runtime\Vtiger_Language_Handler::translate('LBL_' . date('M', strtotime($m[1] . '-' . $m[0] . '-' . '1'))) . ' ' . $m[1];
						}
					} else {
						$label = $row[$gFieldModel->get('reportlabel')];
					}
					$labels[] = (strlen($label) > 30) ? substr($label, 0, 30) . '..' : $label;
					$links[] = $this->generateLink($gFieldModel->get('reportcolumninfo'), $row[$gFieldModel->get('reportlabel')]);
				}
			}
		}

		$data = array('labels' => $labels,
			'values' => $values,
			'links' => $links,
			'type' => (count($values[0]) == 1) ? 'singleBar' : 'multiBar',
			'data_labels' => $this->getDataLabels(),
			'graph_label' => $this->getGraphLabel()
		);
		return $data;
	}

	public function getDataLabels()
	{
		$dataLabels = array();
		if ($this->isRecordCount()) {
			$dataLabels[] = \App\Runtime\Vtiger_Language_Handler::translate('LBL_RECORD_COUNT', 'Reports');
		}
		$queryColumnsByFieldModel = $this->getQueryColumnsByFieldModel();
		if ($queryColumnsByFieldModel) {
			foreach ($queryColumnsByFieldModel as $fieldModel) {
				$fieldTranslatedLabel = $this->getTranslatedLabelFromReportLabel($fieldModel->get('reportlabel'));
				$reportColumn = $fieldModel->get('reportcolumninfo');
				$reportColumnInfo = explode(':', $reportColumn);

				$aggregateFunction = $reportColumnInfo[5];
				$aggregateFunctionLabel = $this->getAggregateFunctionLabel($aggregateFunction);

				$dataLabels[] = \App\Runtime\Vtiger_Language_Handler::translate($aggregateFunctionLabel, 'Reports', $fieldTranslatedLabel);
			}
		}
		return $dataLabels;
	}
}