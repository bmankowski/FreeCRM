<?php

namespace FreeCRM\Modules\Settings\Inventory\Actions;
use FreeCRM\HttpVtiger_Request;
use FreeCRM\Modules\Settings\InventoryModels\Record as Settings_Inventory_Record_Model;



/**
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \FreeCRM\Modules\Settings\Vtiger\Actions\Basic
{

	public function __construct()
	{
		parent::__construct();
		$this->exposeMethod('checkDuplicateName');
		$this->exposeMethod('deleteInventory');
		$this->exposeMethod('saveConfig');
	}

	public function process(Vtiger_Request $request)
	{
		$mode = $request->getMode();
		$currentUser = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		$id = $request->get('id');
		$type = $request->get('view');
		if (empty($id)) {
			$recordModel = new Settings_Inventory_Record_Model();
		} else {
			$recordModel = Settings_Inventory_Record_Model::getInstanceById($id, $type);
		}
		$fields = $request->getAll();
		foreach ($fields as $fieldName => $fieldValue) {
			if ($request->has($fieldName) && !in_array($fieldName, ['module', 'parent', 'view', '__vtrftk', 'action'])) {
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		$recordModel->setType($type);

		$response = new \FreeCRM\Http\Vtiger_Response();
		try {
			$id = $recordModel->save();
			$recordModel = Settings_Inventory_Record_Model::getInstanceById($id, $type);
			$response->setResult(array_merge(['_editurl' => $recordModel->getEditUrl(), 'row_type' => $currentUser->get('rowheight')], $recordModel->getData()));
		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}
		$response->emit();
	}

	public function checkDuplicateName(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$id = $request->get('id');
		$name = $request->get('name');
		$type = $request->get('view');

		$exists = Settings_Inventory_Record_Model::checkDuplicate($name, $id, $type);

		if (!$exists) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'message' => vtranslate('LBL_NAME_EXIST', $qualifiedModuleName));
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function deleteInventory(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$params = $request->get('param');
		$id = $params['id'];
		$type = $params['view'];

		$recordModel = Settings_Inventory_Record_Model::getInstanceById($id, $type);
		$status = $recordModel->delete();

		if (!$status) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'message' => vtranslate('LBL_DELETE_OK', $qualifiedModuleName));
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function saveConfig(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$params = $request->get('param');
		$type = $params['view'];

		$recordModel = \FreeCRM\Modules\Settings\Inventory\Models\Module::getCleanInstance();
		$status = $recordModel->setConfig($type, $params['param']);

		if (!$status) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true);
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
