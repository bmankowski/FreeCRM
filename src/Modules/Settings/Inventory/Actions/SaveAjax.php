<?php

namespace App\Modules\Settings\Inventory\Actions;
use App\HttpVtiger_Request;



/**
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class SaveAjax extends \App\Modules\Settings\Vtiger\Actions\Basic
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
		$currentUser = \App\Modules\Users\Models\Record::getCurrentUserModel();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
		$id = $request->get('id');
		$type = $request->get('view');
		if (empty($id)) {
			$recordModel = new \App\Modules\Settings\Inventory\Models\Record();
		} else {
			$recordModel = \App\Modules\Settings\Inventory\Models\Record::getInstanceById($id, $type);
		}
		$fields = $request->getAll();
		foreach ($fields as $fieldName => $fieldValue) {
			if ($request->has($fieldName) && !in_array($fieldName, ['module', 'parent', 'view', '__vtrftk', 'action'])) {
				$recordModel->set($fieldName, $fieldValue);
			}
		}
		$recordModel->setType($type);

		$response = new \App\Http\Vtiger_Response();
		try {
			$id = $recordModel->save();
			$recordModel = \App\Modules\Settings\Inventory\Models\Record::getInstanceById($id, $type);
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

		$exists = \App\Modules\Settings\Inventory\Models\Record::checkDuplicate($name, $id, $type);

		if (!$exists) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_NAME_EXIST', $qualifiedModuleName));
		}

		$response = new \App\Http\Vtiger_Response();
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

		$recordModel = \App\Modules\Settings\Inventory\Models\Record::getInstanceById($id, $type);
		$status = $recordModel->delete();

		if (!$status) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true, 'message' => \App\Runtime\Vtiger_Language_Handler::translate('LBL_DELETE_OK', $qualifiedModuleName));
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function saveConfig(Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$qualifiedModuleName = $request->getModule(false);
		$params = $request->get('param');
		$type = $params['view'];

		$recordModel = \App\Modules\Settings\Inventory\Models\Module::getCleanInstance();
		$status = $recordModel->setConfig($type, $params['param']);

		if (!$status) {
			$result = array('success' => false);
		} else {
			$result = array('success' => true);
		}

		$response = new \App\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}

	public function validateRequest(Vtiger_Request $request)
	{
		$request->validateWriteAccess();
	}
}
