<?php

namespace FreeCRM\Modules\Vtiger\Actions;

/**
 * Watchdog Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Watchdog extends \FreeCRM\Runtime\Vtiger_Action_Controller
{

	public function checkPermission(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (empty($recordId)) {
			if (!\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'WatchingModule')) {
				throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
			}
		} else {
			if (!\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $recordId) || !\FreeCRM\Modules\Users\Models\Privileges::isPermitted($moduleName, 'WatchingRecords')) {
				throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
			}
		}
		if ($request->has('user')) {
			$userList = array_keys(\App\Fields\Owner::getInstance()->getAccessibleUsers());
			if (!in_array($request->get('user'), $userList)) {
				throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
			}
		}
		return true;
	}

	public function process(\FreeCRM\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$state = $request->get('state');
		$user = false;
		if ($request->has('user')) {
			$user = $request->get('user');
		}
		if (empty($record)) {
			$watchdog = Vtiger_Watchdog_Model::getInstance($moduleName, $user);
			$watchdog->changeModuleState($state);
		} else {
			$watchdog = Vtiger_Watchdog_Model::getInstanceById($record, $moduleName, $user);
			$watchdog->changeRecordState($state);
		}
		Vtiger_Watchdog_Model::reloadCache();
		$response = new \FreeCRM\Http\Vtiger_Response();
		$response->setResult($state);
		$response->emit();
	}
}
