<?php

namespace FreeCRM\Modules\OpenStreetMap\Actions;

/**
 * Action to clipboard
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class ClipBoard extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('save');
		$this->exposeMethod('delete');
		$this->exposeMethod('addAllRecords');
		$this->exposeMethod('addRecord');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function addAllRecords(\FreeCRM\Http\Vtiger_Request $request)
	{
		$coordinatesModel = OpenStreetMap_Coordinate_Model::getInstance();
		$coordinatesModel->set('moduleName', $request->get('srcModule'));
		$count = $coordinatesModel->saveAllRecordsToCache();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(['count' => $count]);
		$response->emit();
	}

	public function delete(\FreeCRM\Http\Vtiger_Request $request)
	{
		$coordinatesModel = OpenStreetMap_Coordinate_Model::getInstance();
		$coordinatesModel->set('moduleName', $request->get('srcModule'));
		$coordinatesModel->deleteCache();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(0);
		$response->emit();
	}

	public function save(\FreeCRM\Http\Vtiger_Request $request)
	{
		$records = $request->get('recordIds');
		$coordinatesModel = OpenStreetMap_Coordinate_Model::getInstance();
		$coordinatesModel->set('moduleName', $request->get('srcModule'));
		$coordinatesModel->deleteCache();
		$coordinatesModel->saveCache($records);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(count($records));
		$response->emit();
	}

	public function addRecord(\FreeCRM\Http\Vtiger_Request $request)
	{
		$record = $request->get('record');
		$srcModuleName = $request->get('srcModuleName');
		$coordinatesModel = OpenStreetMap_Coordinate_Model::getInstance();
		$coordinatesModel->set('moduleName', $srcModuleName);
		$coordinatesModel->addCache($record);
		$moduleModel = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($srcModuleName);
		$coordinatesModel->set('srcModuleModel', $moduleModel);
		$coordinates = $coordinatesModel->readCoordinatesByRecords([$record]);
		if(empty($coordinates)) {
			$coordinates = vtranslate('ERR_ADDRESS_NOT_FOUND', 'OpenStreetMap');
		}
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($coordinates);
		$response->emit();
	}
}
