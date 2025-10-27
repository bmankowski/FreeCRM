<?php

namespace App\Modules\Settings\Users\Actions;

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
		$eventHandler = new \App\EventHandler();
		$eventHandler->trigger('UserLogoutBefore');
		if (\App\AppConfig::main('session_regenerate_id')) {
			\App\Http\Vtiger_Session::regenerateId(true); // to overcome session id reuse.
		}
		\App\Http\Vtiger_Session::destroy();

		//Track the logout History
		$moduleModel = \App\Modules\Users\Models\Module::getInstance('Users');
		$moduleModel->saveLogoutHistory();
		//End
		header('Location: index.php');
	}

	public function postProcess(\App\Http\Vtiger_Request $request)
	{
		// No post-processing needed for logout
	}
}
