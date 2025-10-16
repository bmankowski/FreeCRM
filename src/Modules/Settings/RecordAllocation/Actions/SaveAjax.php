<?php

namespace FreeCRM\Modules\Settings\RecordAllocation\Actions;
use FreeCRM\Modules\Settings\Vtiger\Models\Tracker;



/**
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Save
{

	public function __construct()
	{
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::lockTracking();
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('removePanel');
	}

	public function save(\FreeCRM\Http\Vtiger_Request $request)
	{
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::lockTracking(false);
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::addBasic('save');
		$data = $request->get('param');
		$qualifiedModuleName = $request->getModule(false);

		$oldValues = \FreeCRM\Modules\Settings\RecordAllocation\Models\Module::getRecordAllocationByModule($data['type'], $data['module']);
		$oldValues = array_merge((array) $oldValues[$data['userid'][0]]['users'], (array) $oldValues[$data['userid'][0]]['groups']);

		$moduleInstance = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
		$moduleInstance->set('type', $data['type']);
		$moduleInstance->save(array_filter($data));
		\FreeCRM\Modules\Settings\RecordAllocation\Models\Module::resetDataVariable();
		$newValues = \FreeCRM\Modules\Settings\RecordAllocation\Models\Module::getRecordAllocationByModule($data['type'], $data['module']);
		$newValues = array_merge((array) $newValues[$data['userid'][0]]['users'], (array) $newValues[$data['userid'][0]]['groups']);
		$prevDetail['userId'] = implode(',', $oldValues);
		$newDetail['userId'] = implode(',', $newValues);

		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::addDetail($prevDetail, $newDetail);
		$responceToEmit = new \FreeCRM\Http\Vtiger_Response();
		$responceToEmit->setResult(true);
		$responceToEmit->emit();
	}

	public function removePanel(\FreeCRM\Http\Vtiger_Request $request)
	{
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::lockTracking(false);
		\FreeCRM\Modules\Settings\Vtiger\Models\Tracker::addBasic('delete');
		$data = $request->get('param');
		$moduleName = $data['module'];
		$qualifiedModuleName = $request->getModule(false);

		$moduleInstance = Settings_Vtiger_Module_Model::getInstance($qualifiedModuleName);
		$moduleInstance->set('type', $data['type']);
		$moduleInstance->remove($moduleName);

		$responceToEmit = new \FreeCRM\Http\Vtiger_Response();
		$responceToEmit->setResult(true);
		$responceToEmit->emit();
	}
}
