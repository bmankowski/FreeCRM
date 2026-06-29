<?php

namespace App\Modules\Base\Models;

/**
 * ExportToXml Model Class
 * @package YetiForce.Model
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class ExportToXml extends Export
{

	protected $attrList = ['crmfield', 'crmfieldtype', 'partvalue', 'constvalue', 'refmoule', 'spec', 'refkeyfld', 'delimiter', 'testcondition'];
	protected $product = false;
	protected $tplName = '';
	protected $tmpXmlPath = '';
	protected $inventoryFields;

	public function exportData(\App\Http\Vtiger_Request $request)
	{
		if ($request->get('xmlExportType')) {
			$this->tplName = $request->get('xmlExportType');
		}
		$query = $this->getExportQuery($request);
		$fileName = str_replace(' ', '_', \App\Utils\ListViewUtils::decodeHtml(\App\Runtime\Vtiger_Language_Handler::translate($this->moduleName, $this->moduleName)));
		$entries = $query->all();
		$entriesInventory = [];
		if ($this->moduleInstance->isInventory()) {
			foreach ($entries as $key => $recordData) {
				$entriesInventory[$key] = $this->getEntriesInventory($recordData);
			}
		}

		$this->tmpXmlPath = 'cache/import/' . uniqid() . '.xml';
		if (1 === count($entries)) {
			$key = array_key_first($entries);
			if ($this->tplName) {
				$this->createXmlFromTemplate($entries[$key], $entries[$key]);
			} else {
				$this->createXml($this->sanitizeValues($entries[$key]), $entriesInventory[$key] ?? null);
			}
		} elseif (count($entries) > 1) {
			$this->createMultiRecordXml($entries, $entriesInventory);
		}

		$this->outputFile($fileName);
	}

	/**
	 * Function returns data from advanced block
	 * @param array $recordData
	 * @return array
	 */
	public function getEntriesInventory($recordData)
	{
		$entries = [];
		$inventoryFieldModel = \App\Modules\Base\Models\InventoryField::getInstance($this->moduleName);
		$this->inventoryFields = $inventoryFieldModel->getFields();
		$table = $inventoryFieldModel->getTableName('data');
		$dataReader = (new \App\Db\Query())->from($table)->where(['id' => $recordData['id']])->orderBy('seq', SORT_ASC)->createCommand()->query();
		while ($inventoryRow = $dataReader->read()) {
			$entries[] = $inventoryRow;
		}
		return $entries;
	}

	public function sanitizeInventoryValue($value, $columnName, $formated = false)
	{
		$field = $this->inventoryFields[$columnName];
		if (!empty($field)) {
			if (in_array($field->getName(), ['Name', 'Reference'])) {
				$value = trim($value);
				if (!empty($value)) {
					$recordModule = \App\Records\Record::getType($value);
					$displayValue = \App\Records\Record::getLabel($value);
					if (!empty($recordModule) && !empty($displayValue)) {
						$value = $recordModule . '::::' . $displayValue;
					} else {
						$value = '';
					}
				} else {
					$value = '';
				}
			} elseif ($field->getName() === 'Currency') {
				$value = $field->getDisplayValue($value);
			} else {
				$value;
			}
		} elseif (in_array($columnName, ['taxparam', 'discountparam', 'currencyparam'])) {
			switch ($columnName) {
				case 'currencyparam':
					$field = $this->inventoryFields['currency'];
					$valueData = $field->getCurrencyParam([], $value);
					$valueNewData = [];
					foreach ($valueData as $currencyId => &$data) {
						$currencyName = \vtlib\Functions:: getCurrencyName($currencyId, false);
						$data['value'] = $currencyName;
						$valueNewData[$currencyName] = $data;
					}
					$value = \App\Utils\Json::encode($valueNewData);
					break;
				default:
					break;
			}
		}
		return html_entity_decode($value);
	}

	public function outputFile($fileName)
	{
		header("Content-Disposition:attachment;filename=$fileName.xml");
		header("Content-Type:application/xml;charset=UTF-8");
		header("Expires: Mon, 31 Dec 2000 00:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: post-check=0, pre-check=0", false);

		readfile($this->tmpXmlPath);
	}

	protected function createMultiRecordXml(array $entries, array $entriesInventory): void
	{
		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->setIndent(true);
		$xml->startDocument('1.0', 'UTF-8');
		$xml->startElement('MODULES_EXPORT');
		$xml->writeAttribute('module', $this->moduleName);

		foreach ($entries as $key => $data) {
			$this->writeModuleFieldsBlock($xml, $this->sanitizeValues($data), $entriesInventory[$key] ?? null);
		}

		$xml->endElement();
		file_put_contents($this->tmpXmlPath, $xml->flush(true));
	}

	public function createXml($entries, $entriesInventory)
	{
		$xml = new \XMLWriter();
		$xml->openMemory();
		$xml->setIndent(true);
		$xml->startDocument('1.0', 'UTF-8');
		$this->writeModuleFieldsBlock($xml, $entries, $entriesInventory);
		file_put_contents($this->tmpXmlPath, $xml->flush(true));
	}

	protected function writeModuleFieldsBlock(\XMLWriter $xml, array $entries, ?array $entriesInventory): void
	{
		$xml->startElement('MODULE_FIELDS');
		$xml->writeAttribute('module', $this->moduleName);
		foreach ($this->moduleFieldInstances as $fieldName => $fieldModel) {
			if (!in_array($fieldModel->get('presence'), [0, 2])) {
				continue;
			}
			$xml->startElement($fieldName);
			$xml->writeAttribute('label', \App\Runtime\Vtiger_Language_Handler::translate(html_entity_decode($fieldModel->get('label'), ENT_QUOTES), $this->moduleName));
			$value = $entries[$fieldName] ?? ($entries[$fieldModel->get('column')] ?? '');
			if ($this->isCData($fieldName)) {
				$xml->writeCData((string) $value);
			} else {
				$xml->text((string) $value);
			}
			$xml->endElement();
		}
		if ($entriesInventory) {
			$customColumns = [];
			$xml->startElement('INVENTORY_ITEMS');
			foreach ($entriesInventory as $inventory) {
				unset($inventory['id']);
				$xml->startElement('INVENTORY_ITEM');
				foreach ($inventory as $columnName => $value) {
					$xml->startElement($columnName);
					$fieldModel = $this->inventoryFields[$columnName];
					if ($fieldModel) {
						$xml->writeAttribute('label', \App\Runtime\Vtiger_Language_Handler::translate(html_entity_decode($fieldModel->get('label'), ENT_QUOTES), $this->moduleName));
						if (!in_array($columnName, $customColumns)) {
							foreach ($fieldModel->getCustomColumn() as $key => $dataType) {
								$customColumns[$key] = $columnName;
							}
						}
					}
					if ($this->isCData($columnName, $customColumns)) {
						$xml->writeCData($this->sanitizeInventoryValue($value, $columnName, true));
					} else {
						$xml->text($this->sanitizeInventoryValue($value, $columnName, true));
					}
					$xml->endElement();
				}
				$xml->endElement();
			}
			$xml->endElement();
		}
		$xml->endElement();
	}

	public function isCData($name, $customColumns = [])
	{
		if ($customColumns) {
			return array_key_exists($name, $customColumns);
		}
		$fieldModel = $this->moduleFieldInstances[$name];
		if ($fieldModel && $fieldModel->getFieldDataType() == 'text') {
			return true;
		}
		return false;
	}

	public function createXmlFromTemplate($entries, $entriesInventory)
	{
		
	}
}
