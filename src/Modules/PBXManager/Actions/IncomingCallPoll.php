<?php

namespace FreeCRM\Modules\PBXManager\Actions;

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * Contributor(s): YetiForce.com
 * *********************************************************************************** */
include_once ROOT_DIRECTORY . '/src/Webservices/Create.php';
include_once ROOT_DIRECTORY . '/src/utils/utils.php';

class IncomingCallPoll extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function __construct()
	{
		$this->exposeMethod('searchIncomingCalls');
		$this->exposeMethod('createRecord');
		$this->exposeMethod('getCallStatus');
		$this->exposeMethod('checkModuleViewPermission');
		$this->exposeMethod('checkPermissionForPolling');
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$mode = $request->getMode();
		if (!empty($mode) && $this->isMethodExposed($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$userPrivilegesModel = \FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($request->getModule());

		if (!$permission) {
			throw new \Exception\NoPermitted('LBL_PERMISSION_DENIED');
		}
	}

	public function checkModuleViewPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$response = new \FreeCRM\Http\Vtiger_Response();
		$modules = array('Contacts', 'Leads');
		$view = $request->get('view');
		\FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		foreach ($modules as $module) {
			if (\FreeCRM\Modules\Users\Models\Privileges::isPermitted($module, $view)) {
				$result['modules'][$module] = true;
			} else {
				$result['modules'][$module] = false;
			}
		}
		$response->setResult($result);
		$response->emit();
	}

	public function searchIncomingCalls(\FreeCRM\Http\Vtiger_Request $request)
	{
		$recordModel = \FreeCRM\Modules\PBXManager\Models\Record::getCleanInstance($request->getModule());
		$response = new \FreeCRM\Http\Vtiger_Response();
		$user = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();

		$recordModels = $recordModel->searchIncomingCall();
		// To check whether user have permission on caller record
		if ($recordModels) {
			foreach ($recordModels as $recordModel) {
				// To check whether the user has permission to see contact name in popup
				$recordModel->set('callername', null);

				$callerid = $recordModel->get('customer');
				if ($callerid) {
					$moduleName = $recordModel->get('customertype');
					if (!\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $callerid)) {
						$name = $recordModel->get('customernumber') . vtranslate('LBL_HIDDEN', 'PBXManager');
						$recordModel->set('callername', $name);
					} else {
						$entityNames = getEntityName($moduleName, array($callerid));
						$callerName = $entityNames[$callerid];
						$recordModel->set('callername', $callerName);
					}
				}
				// End
				$direction = $recordModel->get('direction');
				if ($direction == 'inbound') {
					$userid = $recordModel->get('user');
					if ($userid) {
						$entityNames = getEntityName('Users', array($userid));
						$userName = $entityNames[$userid];
						$recordModel->set('answeredby', $userName);
					}
				}
				$recordModel->set('current_user_id', $user->id);
				$calls[] = $recordModel->getData();
			}
		}
		$response->setResult($calls);
		$response->emit();
	}

	public function createRecord(\FreeCRM\Http\Vtiger_Request $request)
	{
		$user = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$moduleName = $request->get('modulename');
		$name = explode("@", $request->get('email'));
		$element['lastname'] = $name[0];
		$element['email'] = $request->get('email');
		$element['phone'] = $request->get('number');
		$element['assigned_user_id'] = vtws_getWebserviceEntityId('Users', $user->id);

		$moduleInstance = \FreeCRM\Modules\Vtiger\Models\Module::getInstance($moduleName);
		$mandatoryFieldModels = $moduleInstance->getMandatoryFieldModels();
		foreach ($mandatoryFieldModels as $mandatoryField) {
			$fieldName = $mandatoryField->get('name');
			$fieldType = $mandatoryField->getFieldDataType();
			$defaultValue = decode_html($mandatoryField->get('defaultvalue'));
			if (!empty($element[$fieldName])) {
				continue;
			} else {
				$fieldValue = $defaultValue;
				if (empty($fieldValue)) {
					$fieldValue = \FreeCRM\Modules\Vtiger\Util::getDefaultMandatoryValue($fieldType);
				}
				$element[$fieldName] = $fieldValue;
			}
		}

		$entity = vtws_create($moduleName, $element, $user);
		$this->updateCustomerInPhoneCalls($entity, $request);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult(true);
		$response->emit();
	}

	public function updateCustomerInPhoneCalls($customer, $request)
	{
		$id = vtws_getIdComponents($customer['id']);
		$sourceuuid = $request->get('callid');
		$module = $request->get('modulename');
		$recordModel = \FreeCRM\Modules\PBXManager\Models\Record::getInstanceBySourceUUID($sourceuuid);
		$recordModel->updateCallDetails(array('customer' => $id[1], 'customertype' => $module));
	}

	public function getCallStatus($request)
	{
		$phonecallsid = $request->get('callid');
		$recordModel = \FreeCRM\Modules\PBXManager\Models\Record::getInstanceById($phonecallsid);
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($recordModel->get('callstatus'));
		$response->emit();
	}

	public function checkPermissionForPolling(\FreeCRM\Http\Vtiger_Request $request)
	{
		\FreeCRM\Modules\Users\Models\Privileges::getCurrentUserPrivilegesModel();
		$callPermission = \FreeCRM\Modules\Users\Models\Privileges::isPermitted('PBXManager', 'ReceiveIncomingCalls');

		$serverModel = PBXManager_Server_Model::getInstance();
		$gateway = $serverModel->get("gateway");

		$user = \FreeCRM\Modules\Users\Models\Record::getCurrentUserModel();
		$userNumber = $user->phone_crm_extension;

		$result = false;
		if ($callPermission && $userNumber && $gateway) {
			$result = true;
		}

		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}

?>
