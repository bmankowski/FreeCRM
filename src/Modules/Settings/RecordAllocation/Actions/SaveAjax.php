<?php

namespace App\Modules\Settings\RecordAllocation\Actions;
use App\Modules\Settings\Base\Models\Tracker;



/**
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \App\Modules\Settings\Base\Actions\Save
{

	public function __construct()
	{
		\App\Modules\Settings\Base\Models\Tracker::lockTracking();
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('removePanel');
	}

	public function save(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Settings\Base\Models\Tracker::lockTracking(false);
		\App\Modules\Settings\Base\Models\Tracker::addBasic('save');
		$data = $request->get('param');
		$qualifiedModuleName = $request->getModule(false);

		$oldValues = \App\Modules\Settings\RecordAllocation\Models\Module::getRecordAllocationByModule($data['type'], $data['module']);
		$oldValues = array_merge((array) $oldValues[$data['userid'][0]]['users'], (array) $oldValues[$data['userid'][0]]['groups']);

		$moduleInstance = \App\Modules\Settings\Base\Models\Module::getInstance($qualifiedModuleName);
		$moduleInstance->set('type', $data['type']);
		$moduleInstance->save(array_filter($data));
		\App\Modules\Settings\RecordAllocation\Models\Module::resetDataVariable();
		$newValues = \App\Modules\Settings\RecordAllocation\Models\Module::getRecordAllocationByModule($data['type'], $data['module']);
		$newValues = array_merge((array) $newValues[$data['userid'][0]]['users'], (array) $newValues[$data['userid'][0]]['groups']);
		$prevDetail['userId'] = implode(',', $oldValues);
		$newDetail['userId'] = implode(',', $newValues);

		\App\Modules\Settings\Base\Models\Tracker::addDetail($prevDetail, $newDetail);
		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult(true);
		$responceToEmit->emit();
	}

	public function removePanel(\App\Http\Vtiger_Request $request)
	{
		\App\Modules\Settings\Base\Models\Tracker::lockTracking(false);
		\App\Modules\Settings\Base\Models\Tracker::addBasic('delete');
		$data = $request->get('param');
		$moduleName = $data['module'];
		$qualifiedModuleName = $request->getModule(false);

		$moduleInstance = \App\Modules\Settings\Base\Models\Module::getInstance($qualifiedModuleName);
		$moduleInstance->set('type', $data['type']);
		$moduleInstance->remove($moduleName);

		$responceToEmit = new \App\Http\Vtiger_Response();
		$responceToEmit->setResult(true);
		$responceToEmit->emit();
	}
}
