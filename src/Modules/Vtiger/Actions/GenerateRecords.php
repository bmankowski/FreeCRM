<?php

namespace App\Modules\Vtiger\Actions;

/**
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class GenerateRecords extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'RecordMappingList') ||
			!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'CreateView')) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function checkMandatoryFields($recordModel)
	{
		$mandatoryFields = $recordModel->getModule()->getMandatoryFieldModels();
		foreach ($mandatoryFields as $field) {
			if (empty($recordModel->get($field->getName()))) {
				return true;
			}
		}
		return false;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$records = $request->get('records');
		$template = $request->get('template');
		$targetModuleName = $request->get('target');
		$method = $request->get('method');
		$success = [];
		if (!empty($template)) {
			$templateRecord = \App\Modules\Vtiger\Models\MappedFields::getInstanceById($template);
			foreach ($records as $recordId) {
				if ($templateRecord->checkFiltersForRecord(intval($recordId))) {
					if ($method == 0) {
						$recordModel = \App\Modules\Vtiger\Models\Record::getCleanInstance($targetModuleName);
						$parentRecordModel = \App\Modules\Vtiger\Models\Record::getInstanceById($recordId);
						$recordModel->setRecordFieldValues($parentRecordModel);
						if ($this->checkMandatoryFields($recordModel)) {
							continue;
						}
						$recordModel->save();
						if (\App\Utils\Utils::isRecordExists($recordModel->getId())) {
							$success[] = $recordId;
						}
					} else {
						$success[] = $recordId;
					}
				}
			}
		}
		$output = ['all' => count($records), 'ok' => $success, 'fail' => array_diff($records, $success)];
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($output);
		$response->emit();
	}

	public function validateRequest(\App\Http\Vtiger_Request $request)
	{
		return $request->validateWriteAccess();
	}
}
