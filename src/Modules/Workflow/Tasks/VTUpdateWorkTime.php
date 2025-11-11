<?php
/* {[The file is published on the basis of YetiForce Public License that can be found in the following directory: licenses/License.html]} */


namespace App\Modules\Workflow\Tasks;

use App\Modules\Workflow\VTTask;

class VTUpdateWorkTime extends VTTask
{

	public $executeImmediately = false;

	public function getFieldNames(): array
	{
		return [];
	}

	/**
	 * Execute task
	 * @param \App\Modules\Base\Models\Record $recordModel
	 */
	public function doTask($recordModel)
	{
		if (!vglobal('workflowIdsAlreadyDone')) {
			vglobal('workflowIdsAlreadyDone', []);
		}
		$globalIds = vglobal('workflowIdsAlreadyDone');
		$db = \App\Database\PearDatabase::getInstance();
		$referenceIds = [];
		$referenceName = \App\Modules\OSSTimeControl\Models\Record::$referenceFieldsToTime;

		foreach ($referenceName as $name) {
			if ($recordModel->get($name)) {
				$referenceIds[$recordModel->get($name)] = $name;
			}
		}
		$delta = \App\Json::decode($this->getContents($recordModel));
		if (is_array($delta)) {
			foreach ($delta as $fieldName => $values) {
				if (!empty($values) && !is_array($values)) {
					$referenceIds[$values] = $fieldName;
				} elseif (is_array($values) && $values['oldValue']) {
					$referenceIds[$values['oldValue']] = $fieldName;
				}
				if (is_array($values) && $values['currentValue']) {
					$referenceIds[$values['currentValue']] = $fieldName;
				}
			}
		}

		$referenceIds = array_diff_key($referenceIds, array_flip($globalIds));
		$metasData = \vtlib\Functions:: getCRMRecordMetadata(array_keys($referenceIds));
		$modulesHierarchy = array_keys(\App\ModuleHierarchy::getModulesHierarchy());
		foreach ($metasData as $referenceId => $metaData) {
			if (((int) $metaData['delete']) === 0 && in_array($metaData['setype'], $modulesHierarchy)) {
				\App\Modules\OSSTimeControl\Models\Record::recalculateTimeControl($referenceId, $referenceIds[$referenceId]);
				$globalIds[] = $referenceId;
			}
		}
		vglobal('workflowIdsAlreadyDone', $globalIds);
	}

	/**
	 * Function to get contents of this task
	 * @param \App\Modules\Base\Models\Record $recordModel
	 * @return <String> contents
	 */
	public function getContents($recordModel)
	{
		if (!$this->contents && is_object($recordModel)) {
			$delta = array_intersect_key($recordModel->getPreviousValue(), array_flip(\App\Modules\OSSTimeControl\Models\Record::$referenceFieldsToTime));

			$this->contents = \App\Json::encode($delta);
		}
		return $this->contents;
	}
}
