<?php

namespace App\Modules\Vtiger\Actions;

/**
 * Watchdog Action Class
 * @package YetiForce.Action
 * @license licenses/License.html
 * @author Mariusz Krzaczkowski <m.krzaczkowski@yetiforce.com>
 * @author Radosław Skrzypczak <r.skrzypczak@yetiforce.com>
 */
class Watchdog extends \App\Runtime\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		if (empty($recordId)) {
			if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'WatchingModule')) {
				throw new \Exception\NoPermittedToRecord('LBL_NO_PERMISSIONS_FOR_THE_RECORD');
			}
		} else {
			if (!\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'DetailView', $recordId) || !\App\Modules\Users\Models\Privileges::isPermitted($moduleName, 'WatchingRecords')) {
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

	public function process(\App\Http\Vtiger_Request $request)
	{
		$moduleName = $request->getModule();
		$record = $request->get('record');
		$state = $request->get('state');
		$user = false;
		if ($request->has('user')) {
			$user = $request->get('user');
		}
		if (empty($record)) {
			$watchdog = \App\Modules\Vtiger\Models\Watchdog::getInstance($moduleName, $user);
			$watchdog->changeModuleState($state);
		} else {
			$watchdog = \App\Modules\Vtiger\Models\Watchdog::getInstanceById($record, $moduleName, $user);
			$watchdog->changeRecordState($state);
		}
		\App\Modules\Vtiger\Models\Watchdog::reloadCache();
		$response = new \App\Http\Vtiger_Response();
		$response->setResult($state);
		$response->emit();
	}
}
