<?php

namespace App\Modules\Settings\CronTasks\Actions;


/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ********************************************************************************** */

class UpdateSequence extends \App\Modules\Settings\Vtiger\Actions\Index
{

	public function process(\App\Http\Vtiger_Request $request)
	{
		$qualifiedModuleName = $request->getModule(false);
		$sequencesList = $request->get('sequencesList');

		$moduleModel = Settings_CronTasks_Module_Model::getInstance($qualifiedModuleName);

		$response = new \App\Http\Vtiger_Response();
		if ($sequencesList) {
			$moduleModel->updateSequence($sequencesList);
			$response->setResult(array(true));
		} else {
			$response->setError();
		}

		$response->emit();
	}
}
