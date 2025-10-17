<?php

namespace FreeCRM\Modules\Settings\MappedFields\Actions;



/**
 * SaveAjax Action Class for MappedFields Settings
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */

use FreeCRM\Modules\Settings\MappedFields\Models\Module as Settings_MappedFields_Module_Model;
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Views\IndexAjax
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('step1');
		$this->exposeMethod('step2');
		$this->exposeMethod('import');
	}

	public function step1(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$params = $request->get('param');
		$recordId = $params['record'];
		$step = $params['step'];

		if ($recordId) {
			$moduleInstance = Settings_MappedFields_Module_Model::getInstanceById($recordId);
		} else {
			$moduleInstance = Settings_MappedFields_Module_Model::getCleanInstance();
		}
		$stepFields = Settings_MappedFields_Module_Model::getFieldsByStep($step);
		foreach ($stepFields as $field) {
			$moduleInstance->getRecord()->set($field, $params[$field]);
			if ($field == 'conditions') {
				$moduleInstance->transformAdvanceFilterToWorkFlowFilter();
			}
		}
		if (!$recordId && $moduleInstance->importsAllowed() >= 1) {
			$message = 'LBL_TEMPATE_EXIST';
		} else {
			$moduleInstance->save();
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['id' => $moduleInstance->getRecordId(), 'message' => \FreeCRM\Runtime\Vtiger_Language_Handler::translate($message, $qualifiedModuleName)]);
		$response->emit();
	}

	public function step2(\FreeCRM\Http\Vtiger_Request $request)
	{
		$params = $request->get('param');
		$recordId = $params['record'];

		$moduleInstance = Settings_MappedFields_Module_Model::getInstanceById($recordId);
		$moduleInstance->getRecord()->set('params', $params['otherConditions']);
		$moduleInstance->setMapping($params['mapping']);
		$moduleInstance->save(true);

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['id' => $moduleInstance->getRecordId()]);
		$response->emit();
	}

	public function import(\FreeCRM\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$moduleInstance = Settings_MappedFields_Module_Model::getCleanInstance();
		$result = $moduleInstance->import($qualifiedModuleName);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
