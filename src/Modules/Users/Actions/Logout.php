<?php

namespace App\Modules\Users\Actions;

/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class Logout extends \App\Base\Controllers\BaseActionController
{

	public function checkPermission(\App\Http\Vtiger_Request $request)
	{
		return true;
	}

	public function process(\App\Http\Vtiger_Request $request)
	{
		$eventHandler = new \App\Events\EventHandler();
		$eventHandler->trigger('UserLogoutBefore');
		if (\App\Core\AppConfig::main('session_regenerate_id')) {
			\App\Http\Vtiger_Session::regenerateId(true); // to overcome session id reuse.
		}
		\App\Http\Vtiger_Session::destroy();

		//Track the logout History
		$moduleName = $request->getModule();
		$moduleModel = \App\Modules\Users\Models\Module::getInstance($moduleName);
		$moduleModel->saveLogoutHistory();
		//End
		header('Location: index.php');
	}
}
