<?php

namespace App\Modules\Workflow\Tasks;

use App\Modules\Workflow\VTTask;

/**
 * Update Related Field Task Handler Class
 * @package YetiForce.WorkflowTask
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 */

class VTUpdateRelatedFieldTask extends VTTask
{

	public $executeImmediately = false;

	public function getFieldNames(): array
	{
		return ['field_value_mapping'];
	}

	/**
	 * Execute task
	 * @param \App\Modules\Base\Models\Record $recordModel
	 */
	public function doTask($recordModel)
	{
		$util = new VTWorkflowUtils();
		$util->adminUser();

		$fieldValueMapping = [];
		if (!empty($this->field_value_mapping)) {
			$fieldValueMapping = \App\Json::decode($this->field_value_mapping);
		}
		if (!empty($fieldValueMapping)) {
			$util->loggedInUser();
			foreach ($fieldValueMapping as $fieldInfo) {
				$relatedData = $fieldInfo['fieldname'];
				$fieldValue = trim($fieldInfo['value']);
				switch ($fieldInfo['valuetype']) {
					case 'fieldname':
						$fieldValue = $recordModel->get($fieldValue);
						break;
					case 'expression':

						$parser = new VTExpressionParser(new VTExpressionSpaceFilter(new VTExpressionTokenizer($fieldValue)));
						$expression = $parser->expression();
						$exprEvaluater = new VTFieldExpressionEvaluater($expression);
						$fieldValue = $exprEvaluater->evaluate($recordModel);
						break;
					default:
						if (preg_match('/([^:]+):boolean$/', $fieldValue, $match)) {
							$fieldValue = $match[1];
							if ($fieldValue == 'true') {
								$fieldValue = '1';
							} else {
								$fieldValue = '0';
							}
						}
						break;
				}
				if (!empty($fieldValue) || $fieldValue == 0) {
					$this->updateRecords($recordModel, $relatedData, $fieldValue);
				}
			}
		}
		$util->revertUser();
	}

	function updateRecords($recordModel, $relatedData, $fieldValue)
	{
		$relatedDataEx = explode('::', $relatedData);
		$relatedModuleName = $relatedDataEx[0];
		$relatedFieldName = $relatedDataEx[1];
		$targetModel = \App\Modules\Base\Models\RelationListView::getInstance($recordModel, $relatedModuleName);
		if (!$targetModel->getRelationModel()) {
			return false;
		}
		$dataReader = $targetModel->getRelationQuery()->select(['vtiger_crmentity.crmid'])
				->createCommand()->query();
		while ($recordId = $dataReader->readColumn(0)) {
			$recordModel = \App\Modules\Base\Models\Record::getInstanceById($recordId, $relatedModuleName);
			$recordModel->setHandlerExceptions(['disableWorkflow' => true]);
			$recordModel->set($relatedFieldName, $fieldValue);
			$recordModel->save();
		}
	}

	/**
	 * Function to get contents of this task
	 * @param <Object> $entity
	 * @return <Array> contents
	 */
	public function getContents($recordModel)
	{
		$this->contents = true;
		return $this->contents;
	}
}
