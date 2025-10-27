<?php

namespace App\Modules\OpenStreetMap\Actions;

/**
 * Action to clipboard
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class ClipBoard extends \App\Base\Controllers\BaseActionController
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
		$this->exposeMethod('addAllRecords');
		$this->exposeMethod('addRecord');
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function addAllRecords(\App\Http\Vtiger_Request $request)
	{
		$coordinatesModel = \App\Modules\OpenStreetMap\Models\Coordinate::getInstance();
		$coordinatesModel->set('moduleName', $request->get('srcModule'));
		$count = $coordinatesModel->saveAllRecordsToCache();
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(['count' => $count]);
		$response->emit();
	}

	public function delete(\App\Http\Vtiger_Request $request)
	{
		$coordinatesModel = \App\Modules\OpenStreetMap\Models\Coordinate::getInstance();
		$coordinatesModel->set('moduleName', $request->get('srcModule'));
		$coordinatesModel->deleteCache();
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(0);
		$response->emit();
	}

	public function save(\App\Http\Vtiger_Request $request)
	{
		$records = $request->get('recordIds');
		$coordinatesModel = \App\Modules\OpenStreetMap\Models\Coordinate::getInstance();
		$coordinatesModel->set('moduleName', $request->get('srcModule'));
		$coordinatesModel->deleteCache();
		$coordinatesModel->saveCache($records);
		$response = new \App\Http\Vtiger_Response();
		$response->setResult(count($records));
		$response->emit();
	}

	public function addRecord(\App\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$srcModuleName = $request->get('srcModuleName');
		$coordinatesModel = \App\Modules\OpenStreetMap\Models\Coordinate::getInstance();
		$coordinatesModel->set('moduleName', $srcModuleName);
		$coordinatesModel->addCache($record);
		$moduleModel = \App\Modules\Base\Models\Module::getInstance($srcModuleName);
		$coordinatesModel->set('srcModuleModel', $moduleModel);
		$coordinates = $coordinatesModel->readCoordinatesByRecords([$record]);
		if(empty($coordinates)) {
			$coordinates = \App\Runtime\Vtiger_Language_Handler::translate('ERR_ADDRESS_NOT_FOUND', 'OpenStreetMap');
		}
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($coordinates);
		$response->emit();
	}
}
