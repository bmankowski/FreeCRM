<?php
/**
 * FreeCRM - Customer Relationship Management System
 *
 * @project FreeCRM
 * @author bmankowski@gmail.com
 * @copyright (c) FreeCRM
 * @license FreeCRM Public License 1.0
 */

declare(strict_types=1);

namespace App\ModuleManagement\Services;

use App\ModuleManagement\Models;

/**
 * FilterService class.
 * 
 * Service for filter/custom view operations.
 */
class FilterService
{
	/** @var \App\Db Database instance */
	private $db;

	/**
	 * Constructor.
	 * 
	 * @param \App\Db\Db $db
	 */
	public function __construct(\App\Db\Db $db)
	{
		$this->db = $db;
	}

	/**
	 * Import filter from XML.
	 * 
	 * @param Models\Module $module Module instance
	 * @param \SimpleXMLElement $filterNode Filter XML node
	 * @param array $fieldsCache Cached field instances
	 * @return void
	 */
	public function importFromXML(Models\Module $module, \SimpleXMLElement $filterNode, array $fieldsCache): void
	{
		$filterName = (string) $filterNode->viewname;
		$isdefault = (string) $filterNode->setdefault === 'true' ? 1 : 0;
		$inmetrics = (string) $filterNode->setmetrics === 'true' ? 1 : 0;
		$presence = isset($filterNode->presence) ? (int) $filterNode->presence : 1;
		$privileges = isset($filterNode->privileges) ? (int) $filterNode->privileges : 1;
		$featured = isset($filterNode->featured) ? (int) $filterNode->featured : 0;
		$sequence = isset($filterNode->sequence) ? (int) $filterNode->sequence : null;
		$description = isset($filterNode->description) ? (string) $filterNode->description : '';
		$sort = isset($filterNode->sort) ? (string) $filterNode->sort : '';

		if ($sequence === null) {
			$maxSequence = (new \App\Db\Query())
				->from('vtiger_customview')
				->where(['entitytype' => $module->getName()])
				->max('sequence');
			$sequence = $maxSequence ? (int) $maxSequence + 1 : 0;
		}

		$status = $presence == 0 ? '0' : '3';

		$this->db->createCommand()->insert('vtiger_customview', [
			'viewname' => $filterName,
			'setdefault' => $isdefault,
			'setmetrics' => $inmetrics,
			'entitytype' => $module->getName(),
			'status' => $status,
			'privileges' => $privileges,
			'featured' => $featured,
			'sequence' => $sequence,
			'presence' => $presence,
			'description' => $description,
			'sort' => $sort,
		])->execute();

		$filterId = $this->db->getLastInsertID('vtiger_customview_cvid_seq');

		// Add fields to filter
		if (!empty($filterNode->fields) && !empty($filterNode->fields->field)) {
			foreach ($filterNode->fields->field as $fieldnode) {
				$fieldName = (string) $fieldnode->fieldname;
				$columnIndex = isset($fieldnode->columnindex) ? (int) $fieldnode->columnindex : 0;

				// Get field from cache
				$fieldInstance = $fieldsCache[$module->getName()][$fieldName] ?? null;
				if (!$fieldInstance) {
					continue;
				}

				$cvcolvalue = $this->getColumnValue($fieldInstance, $module);
				$this->db->createCommand()->update(
					'vtiger_cvcolumnlist',
					['columnindex' => new \yii\db\Expression('columnindex + 1')],
					['and', ['cvid' => $filterId], ['>=', 'columnindex', $columnIndex]]
				)->execute();
				$this->db->createCommand()->insert('vtiger_cvcolumnlist', [
					'cvid' => $filterId,
					'columnindex' => $columnIndex,
					'columnname' => $cvcolvalue
				])->execute();

				// Add rules if present
				if (!empty($fieldnode->rules) && !empty($fieldnode->rules->rule)) {
					foreach ($fieldnode->rules->rule as $rulenode) {
						$ruleColumnIndex = isset($rulenode->columnindex) ? (int) $rulenode->columnindex : 0;
						$comparator = $this->translateComparator((string) $rulenode->comparator);
						$value = (string) $rulenode->value;

						$this->db->createCommand()->update(
							'vtiger_cvadvfilter',
							['columnindex' => new \yii\db\Expression('columnindex + 1')],
							['and', ['cvid' => $filterId], ['>=', 'columnindex', $ruleColumnIndex]]
						)->execute();
						$this->db->createCommand()->insert('vtiger_cvadvfilter', [
							'cvid' => $filterId,
							'columnindex' => $ruleColumnIndex,
							'columnname' => $cvcolvalue,
							'comparator' => $comparator,
							'value' => $value,
							'groupid' => 1,
							'column_condition' => 'and'
						])->execute();
					}
				}
			}
		}
	}

	/**
	 * Export filter to XML.
	 * 
	 * @param Models\Module $module Module instance
	 * @param resource $manifestHandle Manifest file handle
	 * @return void
	 */
	public function exportToXML(Models\Module $module, $manifestHandle): void
	{
		$db = \App\Database\PearDatabase::getInstance();
		$customviewres = $db->pquery("SELECT * FROM vtiger_customview WHERE entitytype = ?", [$module->getName()]);
		if (!$customviewres->rowCount()) {
			return;
		}

		$this->writeNode($manifestHandle, 'customviews', '', true);
		while ($row = $db->getRow($customviewres)) {
			$setdefault = ($row['setdefault'] == 1) ? 'true' : 'false';
			$setmetrics = ($row['setmetrics'] == 1) ? 'true' : 'false';

			$this->writeNode($manifestHandle, 'customview', '', true);
			$this->writeNode($manifestHandle, 'viewname', $row['viewname']);
			$this->writeNode($manifestHandle, 'setdefault', $setdefault);
			$this->writeNode($manifestHandle, 'setmetrics', $setmetrics);
			$this->writeNode($manifestHandle, 'featured', $row['featured']);
			$this->writeNode($manifestHandle, 'privileges', $row['privileges']);
			$this->writeNode($manifestHandle, 'presence', $row['presence']);
			$this->writeNode($manifestHandle, 'sequence', $row['sequence']);
			$this->writeNode($manifestHandle, 'description', '<![CDATA[' . $row['description'] . ']]>');
			$this->writeNode($manifestHandle, 'sort', $row['sort']);

			$this->writeNode($manifestHandle, 'fields', '', true);
			$cvid = $row['cvid'];
			$cvcolumnres = $db->pquery("SELECT * FROM vtiger_cvcolumnlist WHERE cvid=?", [$cvid]);
			while ($cvRow = $db->getRow($cvcolumnres)) {
				$cvColumnNames = explode(':', $cvRow['columnname']);

				$this->writeNode($manifestHandle, 'field', '', true);
				$this->writeNode($manifestHandle, 'fieldname', $cvColumnNames[2]);
				$this->writeNode($manifestHandle, 'columnindex', $cvRow['columnindex']);

				$cvcolumnruleres = $db->pquery("SELECT * FROM vtiger_cvadvfilter WHERE cvid=? && columnname=?", [$cvid, $cvRow['columnname']]);
				if ($cvcolumnruleres->rowCount()) {
					$this->writeNode($manifestHandle, 'rules', '', true);
					while ($rulesRow = $db->getRow($cvcolumnruleres)) {
						$cvColumnRuleComp = $this->translateComparator($rulesRow['comparator'], true);
						$this->writeNode($manifestHandle, 'rule', '', true);
						$this->writeNode($manifestHandle, 'columnindex', $rulesRow['columnindex']);
						$this->writeNode($manifestHandle, 'comparator', $cvColumnRuleComp);
						$this->writeNode($manifestHandle, 'value', $rulesRow['value']);
						$this->writeNode($manifestHandle, 'rule', '', false);
					}
					$this->writeNode($manifestHandle, 'rules', '', false);
				}
				$this->writeNode($manifestHandle, 'field', '', false);
			}
			$this->writeNode($manifestHandle, 'fields', '', false);
			$this->writeNode($manifestHandle, 'customview', '', false);
		}
		$this->writeNode($manifestHandle, 'customviews', '', false);
	}

	/**
	 * Delete a specific filter by ID.
	 * 
	 * @param int $filterId Filter ID
	 * @return void
	 */
	public function delete(int $filterId): void
	{
		$this->db->createCommand()->delete('vtiger_cvadvfilter', ['cvid' => $filterId])->execute();
		$this->db->createCommand()->delete('vtiger_cvcolumnlist', ['cvid' => $filterId])->execute();
		$this->db->createCommand()->delete('vtiger_customview', ['cvid' => $filterId])->execute();
	}

	/**
	 * Delete filters for module.
	 * 
	 * @param int $moduleId Module ID
	 * @return void
	 */
	public function deleteForModule(int $moduleId): void
	{
		$moduleName = (new \App\Db\Query())
			->select(['name'])
			->from('vtiger_tab')
			->where(['tabid' => $moduleId])
			->scalar();

		if (!$moduleName) {
			return;
		}

		$cvids = (new \App\Db\Query())
			->from('vtiger_customview')
			->where(['entitytype' => $moduleName])
			->column();

		if (!empty($cvids)) {
			$this->db->createCommand()->delete('vtiger_cvadvfilter', ['cvid' => $cvids])->execute();
			$this->db->createCommand()->delete('vtiger_cvcolumnlist', ['cvid' => $cvids])->execute();
			$this->db->createCommand()->delete('vtiger_customview', ['cvid' => $cvids])->execute();
		}
	}

	/**
	 * Get filter instance by name.
	 * 
	 * @param string $filterName Filter name
	 * @param Models\Module $module Module instance
	 * @return array|null Filter data or null if not found
	 */
	public function getInstance(string $filterName, Models\Module $module): ?array
	{
		$result = (new \App\Db\Query())
			->from('vtiger_customview')
			->where(['viewname' => $filterName, 'entitytype' => $module->getName()])
			->one();

		return $result ?: null;
	}

	/**
	 * Get column value for custom view.
	 * 
	 * @param Models\Field $fieldInstance Field instance
	 * @param Models\Module $module Module instance
	 * @return string Column value
	 */
	private function getColumnValue(Models\Field $fieldInstance, Models\Module $module): string
	{
		$tod = explode('~', $fieldInstance->getTypeofdata());
		$displayinfo = $module->getName() . '_' . str_replace(' ', '_', $fieldInstance->getLabel()) . ':' . $tod[0];
		return "{$fieldInstance->getTable()}:{$fieldInstance->getColumn()}:{$fieldInstance->getName()}:$displayinfo";
	}

	/**
	 * Translate comparator to short or long form.
	 * 
	 * @param string $value Comparator value
	 * @param bool $toLongForm Whether to convert to long form
	 * @return string Translated comparator
	 */
	private function translateComparator(string $value, bool $toLongForm = false): string
	{
		if ($toLongForm) {
			$comparator = strtolower($value);
			if ($comparator == 'e') {
				return 'EQUALS';
			} elseif ($comparator == 'n') {
				return 'NOT_EQUALS';
			} elseif ($comparator == 's') {
				return 'STARTS_WITH';
			} elseif ($comparator == 'ew') {
				return 'ENDS_WITH';
			} elseif ($comparator == 'c') {
				return 'CONTAINS';
			} elseif ($comparator == 'k') {
				return 'DOES_NOT_CONTAINS';
			} elseif ($comparator == 'l') {
				return 'LESS_THAN';
			} elseif ($comparator == 'g') {
				return 'GREATER_THAN';
			} elseif ($comparator == 'm') {
				return 'LESS_OR_EQUAL';
			} elseif ($comparator == 'h') {
				return 'GREATER_OR_EQUAL';
			}
			return strtoupper($value);
		} else {
			$comparator = strtoupper($value);
			if ($comparator == 'EQUALS') {
				return 'e';
			} elseif ($comparator == 'NOT_EQUALS') {
				return 'n';
			} elseif ($comparator == 'STARTS_WITH') {
				return 's';
			} elseif ($comparator == 'ENDS_WITH') {
				return 'ew';
			} elseif ($comparator == 'CONTAINS') {
				return 'c';
			} elseif ($comparator == 'DOES_NOT_CONTAINS') {
				return 'k';
			} elseif ($comparator == 'LESS_THAN') {
				return 'l';
			} elseif ($comparator == 'GREATER_THAN') {
				return 'g';
			} elseif ($comparator == 'LESS_OR_EQUAL') {
				return 'm';
			} elseif ($comparator == 'GREATER_OR_EQUAL') {
				return 'h';
			}
			return strtolower($value);
		}
	}

	/**
	 * Write XML node to manifest handle.
	 * 
	 * @param resource $handle File handle
	 * @param string $node Node name
	 * @param mixed $value Node value
	 * @param bool $open Whether to open or close node
	 * @return void
	 */
	private function writeNode($handle, string $node, $value = '', bool $open = true): void
	{
		if ($open) {
			if ($value !== '') {
				fwrite($handle, "<$node>" . htmlspecialchars((string) $value, ENT_XML1, 'UTF-8') . "</$node>\n");
			} else {
				fwrite($handle, "<$node>\n");
			}
		} else {
			fwrite($handle, "</$node>\n");
		}
	}
}

